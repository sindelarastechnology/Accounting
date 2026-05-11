<?php

namespace Tests\Feature;

use App\Helpers\AccountResolver;
use App\Models\Account;
use App\Models\InventoryMovement;
use App\Models\Journal;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Services\JournalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Tests\Helpers\TestSetup;
use Tests\TestCase;

class ObserverTest extends TestCase
{
    use RefreshDatabase, TestSetup;

    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestDatabase();

        $user = User::factory()->create();
        Auth::login($user);
        $this->userId = $user->id;
    }

    public function test_account_saved_clears_resolver_cache(): void
    {
        AccountResolver::receivable();
        $this->assertTrue(Cache::has('account_resolver_ar_account_id'));

        $account = Account::find($this->getAccountId('1100-00-010'));
        $account->update(['name' => 'Kas Updated']);

        $this->assertFalse(Cache::has('account_resolver_ar_account_id'));
    }

    public function test_account_deleted_clears_resolver_cache(): void
    {
        $account = Account::factory()->create([
            'code' => '9999-00-099',
            'name' => 'Temp',
            'category' => 'asset',
            'normal_balance' => 'debit',
            'is_header' => false,
            'is_active' => true,
        ]);

        AccountResolver::clearCache();

        $account->delete();
        $this->assertFalse(Cache::has('account_resolver_ar_account_id'));
    }

    public function test_setting_saved_clears_resolver_cache(): void
    {
        AccountResolver::receivable();

        Setting::set('ar_account_id', $this->getAccountId('1300-00-020'), 'integer');

        $this->assertFalse(Cache::has('account_resolver_ar_account_id'));
    }

    public function test_inventory_movement_created_updates_stock(): void
    {
        $product = $this->createProduct(['stock_on_hand' => 10]);
        $cashAccountId = $this->getAccountId('1100-00-010');
        $revenueAccountId = $this->getAccountId('4100-00-010');

        $journal = JournalService::createJournal(
            ['date' => $this->today(), 'period_id' => $this->periodId(), 'description' => 'test', 'source' => 'manual'],
            [
                ['account_id' => $cashAccountId, 'debit_amount' => 100, 'credit_amount' => 0, 'description' => 'DR'],
                ['account_id' => $revenueAccountId, 'debit_amount' => 0, 'credit_amount' => 100, 'description' => 'CR'],
            ]
        );

        InventoryMovement::create([
            'product_id' => $product->id,
            'date' => $this->today(),
            'type' => 'in',
            'ref_type' => 'manual',
            'qty' => 5,
            'unit_cost' => 1000,
            'total_cost' => 5000,
            'description' => 'Test',
            'journal_id' => $journal->id,
            'created_by' => $this->userId,
        ]);

        $product->refresh();
        $this->assertEquals(15, (float) $product->stock_on_hand);
    }

    public function test_inventory_movement_deleted_reverses_stock(): void
    {
        $product = $this->createProduct(['stock_on_hand' => 10]);
        $cashAccountId = $this->getAccountId('1100-00-010');
        $revenueAccountId = $this->getAccountId('4100-00-010');

        $journal = JournalService::createJournal(
            ['date' => $this->today(), 'period_id' => $this->periodId(), 'description' => 'test', 'source' => 'manual'],
            [
                ['account_id' => $cashAccountId, 'debit_amount' => 100, 'credit_amount' => 0, 'description' => 'DR'],
                ['account_id' => $revenueAccountId, 'debit_amount' => 0, 'credit_amount' => 100, 'description' => 'CR'],
            ]
        );

        $movement = InventoryMovement::create([
            'product_id' => $product->id,
            'date' => $this->today(),
            'type' => 'in',
            'ref_type' => 'manual',
            'qty' => 5,
            'unit_cost' => 1000,
            'total_cost' => 5000,
            'description' => 'Test',
            'journal_id' => $journal->id,
            'created_by' => $this->userId,
        ]);

        $movement->delete();

        $product->refresh();
        $this->assertEquals(10, (float) $product->stock_on_hand);
    }

    public function test_wallet_created_with_opening_balance_creates_journal(): void
    {
        $equityAccountId = $this->getAccountId('3100-00-020');
        $cashAccountId = $this->getAccountId('1100-00-010');

        $wallet = Wallet::create([
            'name' => 'Kas Baru',
            'type' => 'cash',
            'account_id' => $cashAccountId,
            'opening_balance' => 1000000,
            'equity_account_id' => $equityAccountId,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('journals', [
            'source' => 'opening',
            'ref_type' => 'wallets',
            'ref_id' => $wallet->id,
        ]);
    }
}
