<?php

namespace Tests\Feature;

use App\Models\Period;
use App\Models\User;
use App\Services\JournalService;
use App\Services\PeriodService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Helpers\TestSetup;
use Tests\TestCase;

class PeriodServiceTest extends TestCase
{
    use RefreshDatabase, TestSetup;

    private int $cashAccountId;
    private int $revenueAccountId;
    private int $expenseAccountId;
    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestDatabase();

        $user = User::factory()->create();
        Auth::login($user);
        $this->userId = $user->id;

        $this->cashAccountId = $this->getAccountId('1100-00-010');
        $this->revenueAccountId = $this->getAccountId('4100-00-010');
        $this->expenseAccountId = $this->getAccountId('6110-00-010');
    }

    public function test_closePeriod_creates_closing_journal(): void
    {
        JournalService::createJournal(
            ['date' => $this->today(), 'period_id' => $this->periodId(), 'description' => 'Revenue', 'source' => 'sale'],
            [
                ['account_id' => $this->cashAccountId, 'debit_amount' => 1000000, 'credit_amount' => 0, 'description' => 'DR'],
                ['account_id' => $this->revenueAccountId, 'debit_amount' => 0, 'credit_amount' => 1000000, 'description' => 'CR'],
            ]
        );

        PeriodService::closePeriod($this->currentPeriod, $this->userId);
        $this->currentPeriod->refresh();

        $this->assertTrue($this->currentPeriod->is_closed);
        $this->assertNotNull($this->currentPeriod->closed_at);
    }

    public function test_closePeriod_already_closed_throws(): void
    {
        $this->expectException(\App\Exceptions\PeriodClosedException::class);

        $this->currentPeriod->update(['is_closed' => true]);
        PeriodService::closePeriod($this->currentPeriod, $this->userId);
    }

    public function test_reopenPeriod(): void
    {
        JournalService::createJournal(
            ['date' => $this->today(), 'period_id' => $this->periodId(), 'description' => 'test', 'source' => 'manual'],
            [
                ['account_id' => $this->cashAccountId, 'debit_amount' => 1000, 'credit_amount' => 0, 'description' => 'DR'],
                ['account_id' => $this->revenueAccountId, 'debit_amount' => 0, 'credit_amount' => 1000, 'description' => 'CR'],
            ]
        );

        // create extra open period spanning today so reversal can be posted
        $tempPeriod = Period::create([
            'name' => 'Temp Open',
            'start_date' => now()->subDay()->format('Y-m-d'),
            'end_date' => now()->addDay()->format('Y-m-d'),
            'is_closed' => false,
        ]);

        PeriodService::closePeriod($this->currentPeriod, $this->userId);
        PeriodService::reopenPeriod($this->currentPeriod, $this->userId, 'Test reopen');

        $this->currentPeriod->refresh();
        $this->assertFalse($this->currentPeriod->is_closed);
        $this->assertNull($this->currentPeriod->closed_at);
    }

    public function test_getNextPeriod(): void
    {
        $next = PeriodService::getNextPeriod($this->currentPeriod);

        $this->assertNotNull($next);
        $this->assertGreaterThan($this->currentPeriod->start_date, $next->start_date);
    }

    public function test_createNextPeriod(): void
    {
        $next = PeriodService::createNextPeriod($this->currentPeriod);

        $this->assertNotNull($next);
        $this->assertFalse($next->is_closed);
        $this->assertEquals(
            $this->currentPeriod->start_date->copy()->addMonth()->startOfMonth()->format('Y-m-d'),
            $next->start_date->format('Y-m-d')
        );
    }

    public function test_createNextPeriod_duplicate_throws(): void
    {
        $this->expectException(\Exception::class);

        PeriodService::createNextPeriod($this->currentPeriod);
        PeriodService::createNextPeriod($this->currentPeriod);
    }
}
