<?php

namespace Tests\Feature;

use App\Exceptions\InvalidAccountException;
use App\Models\Account;
use App\Models\FixedAsset;
use App\Services\FixedAssetService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\TestSetup;
use Tests\TestCase;

class FixedAssetServiceTest extends TestCase
{
    use RefreshDatabase, TestSetup;

    private $assetAccount;
    private $depreciationAccount;
    private $accumulatedAccount;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestDatabase();

        $this->assetAccount = Account::find($this->getAccountId('1700-00-030'));
        $this->depreciationAccount = Account::find($this->getAccountId('6600-00-010'));
        $this->accumulatedAccount = Account::find($this->getAccountId('1700-00-021'));
    }

    private function createAsset(array $overrides = []): FixedAsset
    {
        return FixedAsset::create(array_merge([
            'code' => 'AST-' . fake()->unique()->randomNumber(5),
            'name' => 'Mesin Produksi',
            'acquisition_date' => '2025-01-01',
            'acquisition_cost' => 120000000,
            'salvage_value' => 0,
            'useful_life_years' => 10,
            'depreciation_method' => 'straight_line',
            'accumulated_depreciation' => 0,
            'is_fully_depreciated' => false,
            'asset_account_id' => $this->assetAccount->id,
            'depreciation_account_id' => $this->depreciationAccount->id,
            'accumulated_depreciation_account_id' => $this->accumulatedAccount->id,
            'status' => 'active',
        ], $overrides));
    }

    // ==================== calculateMonthlyDepreciation ====================

    public function test_straight_line_depreciation(): void
    {
        $asset = $this->createAsset([
            'acquisition_cost' => 120000000,
            'salvage_value' => 0,
            'useful_life_years' => 10,
            'depreciation_method' => 'straight_line',
        ]);

        $monthly = FixedAssetService::calculateMonthlyDepreciation($asset);

        $this->assertEquals(1000000, $monthly);
    }

    public function test_straight_line_with_salvage_value(): void
    {
        $asset = $this->createAsset([
            'acquisition_cost' => 120000000,
            'salvage_value' => 12000000,
            'useful_life_years' => 10,
            'depreciation_method' => 'straight_line',
        ]);

        $monthly = FixedAssetService::calculateMonthlyDepreciation($asset);

        $this->assertEquals(900000, $monthly);
    }

    public function test_fully_depreciated_returns_zero(): void
    {
        $asset = $this->createAsset(['is_fully_depreciated' => true]);

        $monthly = FixedAssetService::calculateMonthlyDepreciation($asset);

        $this->assertEquals(0, $monthly);
    }

    public function test_inactive_asset_returns_zero(): void
    {
        $asset = $this->createAsset(['status' => 'disposed']);

        $monthly = FixedAssetService::calculateMonthlyDepreciation($asset);

        $this->assertEquals(0, $monthly);
    }

    public function test_double_declining_method(): void
    {
        $asset = $this->createAsset([
            'acquisition_cost' => 10000000,
            'salvage_value' => 0,
            'useful_life_years' => 5,
            'depreciation_method' => 'double_declining',
            'acquisition_date' => Carbon::now()->subMonths(2)->format('Y-m-d'),
        ]);

        $monthly = FixedAssetService::calculateMonthlyDepreciation($asset);

        $this->assertGreaterThan(0, $monthly);
        // Year 1: 10jt * 40% = 4jt / 12 = 333,333
        $this->assertEqualsWithDelta(333333, $monthly, 1);
    }

    public function test_sum_of_years_method(): void
    {
        $asset = $this->createAsset([
            'acquisition_cost' => 10000000,
            'salvage_value' => 0,
            'useful_life_years' => 5,
            'depreciation_method' => 'sum_of_years',
            'acquisition_date' => Carbon::now()->subMonths(1)->format('Y-m-d'),
        ]);

        $monthly = FixedAssetService::calculateMonthlyDepreciation($asset);

        // Year 1: 10jt * 5/15 = 3,333,333 / 12 = 277,777.75
        $this->assertGreaterThan(0, $monthly);
        $this->assertEqualsWithDelta(277778, $monthly, 1);
    }

    // ==================== recordDepreciation ====================

    public function test_recordDepreciation_creates_journal(): void
    {
        $asset = $this->createAsset();

        $journal = FixedAssetService::recordDepreciation($asset);

        $this->assertNotNull($journal);
        $this->assertEquals(1000000, (float) $journal->lines->firstWhere('account_id', $this->depreciationAccount->id)->debit_amount);
        $this->assertEquals(1000000, (float) $journal->lines->firstWhere('account_id', $this->accumulatedAccount->id)->credit_amount);
    }

    public function test_recordDepreciation_updates_accumulated(): void
    {
        $asset = $this->createAsset();

        FixedAssetService::recordDepreciation($asset);
        $asset->refresh();

        $this->assertEquals(1000000, (float) $asset->accumulated_depreciation);
    }

    public function test_recordDepreciation_duplicate_month_throws(): void
    {
        $asset = $this->createAsset();

        FixedAssetService::recordDepreciation($asset);

        $this->expectException(InvalidAccountException::class);
        FixedAssetService::recordDepreciation($asset);
    }

    public function test_recordDepreciation_non_active_throws(): void
    {
        $asset = $this->createAsset(['status' => 'disposed']);

        $this->expectException(InvalidAccountException::class);
        FixedAssetService::recordDepreciation($asset);
    }

    // ==================== disposeAsset ====================

    public function test_disposeAsset_no_proceeds(): void
    {
        $asset = $this->createAsset();
        FixedAssetService::recordDepreciation($asset);

        FixedAssetService::disposeAsset($asset, $this->today(), 0);
        $asset->refresh();

        $this->assertEquals('disposed', $asset->status);
    }

    public function test_disposeAsset_with_gain(): void
    {
        $asset = $this->createAsset(['acquisition_cost' => 1000000, 'useful_life_years' => 10]);
        FixedAssetService::recordDepreciation($asset);

        FixedAssetService::disposeAsset($asset, $this->today(), 2000000);
        $asset->refresh();

        $this->assertEquals('disposed', $asset->status);
    }

    public function test_disposeAsset_already_disposed_throws(): void
    {
        $asset = $this->createAsset(['status' => 'disposed']);

        $this->expectException(InvalidAccountException::class);
        FixedAssetService::disposeAsset($asset, $this->today(), 0);
    }

    // ==================== reverseDepreciation ====================

    public function test_reverseDepreciation(): void
    {
        $asset = $this->createAsset();
        FixedAssetService::recordDepreciation($asset);

        FixedAssetService::reverseDepreciation($asset);
        $asset->refresh();

        $this->assertEquals(0, (float) $asset->accumulated_depreciation);
        $this->assertEquals('active', $asset->status);
    }
}
