<?php

namespace Tests\Feature;

use App\Exceptions\AccountingImbalanceException;
use App\Exceptions\InvalidAccountException;
use App\Models\Account;
use App\Models\Journal;
use App\Services\JournalService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\TestSetup;
use Tests\TestCase;

class JournalServiceTest extends TestCase
{
    use RefreshDatabase, TestSetup;

    private int $cashAccountId;
    private int $revenueAccountId;
    private int $expenseAccountId;
    private int $arAccountId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestDatabase();

        $this->cashAccountId = $this->getAccountId('1100-00-010');
        $this->revenueAccountId = $this->getAccountId('4100-00-010');
        $this->expenseAccountId = $this->getAccountId('6110-00-010');
        $this->arAccountId = $this->getAccountId('1300-00-020');
    }

    // ==================== createJournal ====================

    public function test_createJournal_with_less_than_2_lines_throws(): void
    {
        $this->expectException(InvalidAccountException::class);
        JournalService::createJournal(
            ['date' => $this->today(), 'period_id' => $this->periodId(), 'description' => 'test'],
            [['account_id' => $this->cashAccountId, 'debit_amount' => 100, 'credit_amount' => 0]]
        );
    }

    public function test_createJournal_with_debit_and_credit_on_same_line_throws(): void
    {
        $this->expectException(InvalidAccountException::class);
        JournalService::createJournal(
            ['date' => $this->today(), 'period_id' => $this->periodId(), 'description' => 'test'],
            [
                ['account_id' => $this->cashAccountId, 'debit_amount' => 100, 'credit_amount' => 50, 'description' => 'Invalid'],
                ['account_id' => $this->revenueAccountId, 'debit_amount' => 0, 'credit_amount' => 50, 'description' => 'CR'],
            ]
        );
    }

    public function test_createJournal_with_zero_values_throws(): void
    {
        $this->expectException(AccountingImbalanceException::class);
        JournalService::createJournal(
            ['date' => $this->today(), 'period_id' => $this->periodId(), 'description' => 'test'],
            [
                ['account_id' => $this->cashAccountId, 'debit_amount' => 0, 'credit_amount' => 0, 'description' => 'Zero'],
                ['account_id' => $this->revenueAccountId, 'debit_amount' => 0, 'credit_amount' => 100, 'description' => 'CR'],
            ]
        );
    }

    public function test_createJournal_with_negative_values_throws(): void
    {
        $this->expectException(InvalidAccountException::class);
        JournalService::createJournal(
            ['date' => $this->today(), 'period_id' => $this->periodId(), 'description' => 'test'],
            [
                ['account_id' => $this->cashAccountId, 'debit_amount' => -100, 'credit_amount' => 0, 'description' => 'Neg'],
                ['account_id' => $this->revenueAccountId, 'debit_amount' => 0, 'credit_amount' => 100, 'description' => 'CR'],
            ]
        );
    }

    public function test_createJournal_with_is_header_account_throws(): void
    {
        $this->expectException(InvalidAccountException::class);

        $headerAccount = Account::create([
            'code' => '9990-00-000',
            'name' => 'Header Test',
            'category' => 'asset',
            'normal_balance' => 'debit',
            'is_header' => true,
            'is_active' => true,
        ]);

        JournalService::createJournal(
            ['date' => $this->today(), 'period_id' => $this->periodId(), 'description' => 'test'],
            [
                ['account_id' => $headerAccount->id, 'debit_amount' => 100, 'credit_amount' => 0, 'description' => 'DR'],
                ['account_id' => $this->revenueAccountId, 'debit_amount' => 0, 'credit_amount' => 100, 'description' => 'CR'],
            ]
        );
    }

    public function test_createJournal_with_inactive_account_throws(): void
    {
        $this->expectException(InvalidAccountException::class);

        $inactiveAccount = Account::create([
            'code' => '9999-00-001',
            'name' => 'Inactive Test',
            'category' => 'asset',
            'normal_balance' => 'debit',
            'is_header' => false,
            'is_active' => false,
        ]);

        JournalService::createJournal(
            ['date' => $this->today(), 'period_id' => $this->periodId(), 'description' => 'test'],
            [
                ['account_id' => $inactiveAccount->id, 'debit_amount' => 100, 'credit_amount' => 0, 'description' => 'DR'],
                ['account_id' => $this->revenueAccountId, 'debit_amount' => 0, 'credit_amount' => 100, 'description' => 'CR'],
            ]
        );
    }

    public function test_createJournal_with_imbalance_throws(): void
    {
        $this->expectException(AccountingImbalanceException::class);
        JournalService::createJournal(
            ['date' => $this->today(), 'period_id' => $this->periodId(), 'description' => 'test'],
            [
                ['account_id' => $this->cashAccountId, 'debit_amount' => 100, 'credit_amount' => 0, 'description' => 'DR'],
                ['account_id' => $this->revenueAccountId, 'debit_amount' => 0, 'credit_amount' => 99, 'description' => 'CR'],
            ]
        );
    }

    public function test_createJournal_with_balanced_lines_creates_journal(): void
    {
        $journal = JournalService::createJournal(
            ['date' => $this->today(), 'period_id' => $this->periodId(), 'description' => 'Test transaction', 'source' => 'manual'],
            [
                ['account_id' => $this->cashAccountId, 'debit_amount' => 100, 'credit_amount' => 0, 'description' => 'DR Cash'],
                ['account_id' => $this->revenueAccountId, 'debit_amount' => 0, 'credit_amount' => 100, 'description' => 'CR Revenue'],
            ]
        );

        $this->assertNotNull($journal);
        $this->assertEquals('Test transaction', $journal->description);
        $this->assertEquals('manual', $journal->source);
        $this->assertEquals(2, $journal->lines->count());
        $this->assertEquals(100, (float) $journal->lines->firstWhere('account_id', $this->cashAccountId)->debit_amount);
        $this->assertEquals(100, (float) $journal->lines->firstWhere('account_id', $this->revenueAccountId)->credit_amount);
    }

    public function test_createJournal_generates_document_number(): void
    {
        $journal = JournalService::createJournal(
            ['date' => $this->today(), 'period_id' => $this->periodId(), 'description' => 'test', 'source' => 'manual'],
            [
                ['account_id' => $this->cashAccountId, 'debit_amount' => 100, 'credit_amount' => 0, 'description' => 'DR'],
                ['account_id' => $this->revenueAccountId, 'debit_amount' => 0, 'credit_amount' => 100, 'description' => 'CR'],
            ]
        );

        $this->assertNotNull($journal->number);
        $this->assertStringContainsString('JNL/', $journal->number);
    }

    public function test_createJournal_sets_is_posted_true(): void
    {
        $journal = JournalService::createJournal(
            ['date' => $this->today(), 'period_id' => $this->periodId(), 'description' => 'test', 'source' => 'manual'],
            [
                ['account_id' => $this->cashAccountId, 'debit_amount' => 100, 'credit_amount' => 0, 'description' => 'DR'],
                ['account_id' => $this->revenueAccountId, 'debit_amount' => 0, 'credit_amount' => 100, 'description' => 'CR'],
            ]
        );

        $this->assertTrue((bool) $journal->is_posted);
    }

    public function test_createJournal_creates_audit_log(): void
    {
        $journal = JournalService::createJournal(
            ['date' => $this->today(), 'period_id' => $this->periodId(), 'description' => 'test', 'source' => 'manual'],
            [
                ['account_id' => $this->cashAccountId, 'debit_amount' => 100, 'credit_amount' => 0, 'description' => 'DR'],
                ['account_id' => $this->revenueAccountId, 'debit_amount' => 0, 'credit_amount' => 100, 'description' => 'CR'],
            ]
        );

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Journal::class,
            'auditable_id' => $journal->id,
            'event' => 'created',
        ]);
    }

    // ==================== voidJournal ====================

    public function test_voidJournal_on_void_journal_throws(): void
    {
        $this->expectException(InvalidAccountException::class);

        $journal = JournalService::createJournal(
            ['date' => $this->today(), 'period_id' => $this->periodId(), 'description' => 'test', 'source' => 'manual', 'type' => 'void'],
            [
                ['account_id' => $this->cashAccountId, 'debit_amount' => 100, 'credit_amount' => 0, 'description' => 'DR'],
                ['account_id' => $this->revenueAccountId, 'debit_amount' => 0, 'credit_amount' => 100, 'description' => 'CR'],
            ]
        );

        JournalService::voidJournal($journal, 'try void');
    }

    public function test_voidJournal_creates_reversal_journal(): void
    {
        $journal = JournalService::createJournal(
            ['date' => $this->today(), 'period_id' => $this->periodId(), 'description' => 'Original', 'source' => 'manual'],
            [
                ['account_id' => $this->cashAccountId, 'debit_amount' => 100, 'credit_amount' => 0, 'description' => 'DR Cash'],
                ['account_id' => $this->revenueAccountId, 'debit_amount' => 0, 'credit_amount' => 100, 'description' => 'CR Revenue'],
            ]
        );

        $reversal = JournalService::voidJournal($journal, 'Cancel transaction');

        $this->assertNotNull($reversal);
        $this->assertEquals('reversal', $reversal->type);

        // Reversal should swap debit and credit
        $reversalLines = $reversal->lines;
        $this->assertEquals(2, $reversalLines->count());
        $this->assertEquals(100, (float) $reversalLines->firstWhere('account_id', $this->cashAccountId)->credit_amount);
        $this->assertEquals(100, (float) $reversalLines->firstWhere('account_id', $this->revenueAccountId)->debit_amount);
    }

    public function test_voidJournal_marks_original_as_void(): void
    {
        $journal = JournalService::createJournal(
            ['date' => $this->today(), 'period_id' => $this->periodId(), 'description' => 'Original', 'source' => 'manual'],
            [
                ['account_id' => $this->cashAccountId, 'debit_amount' => 100, 'credit_amount' => 0, 'description' => 'DR'],
                ['account_id' => $this->revenueAccountId, 'debit_amount' => 0, 'credit_amount' => 100, 'description' => 'CR'],
            ]
        );

        $reversal = JournalService::voidJournal($journal, 'Cancel');

        $journal->refresh();
        $this->assertEquals('void', $journal->type);
        $this->assertEquals($reversal->id, $journal->reversed_by_journal_id);
    }

    // ==================== getAccountBalance ====================

    public function test_getAccountBalance_returns_zero_for_empty_account(): void
    {
        $balance = JournalService::getAccountBalance($this->cashAccountId);

        $this->assertEquals(0, $balance['debit']);
        $this->assertEquals(0, $balance['credit']);
        $this->assertEquals(0, $balance['balance']);
    }

    public function test_getAccountBalance_debit_normal(): void
    {
        JournalService::createJournal(
            ['date' => $this->today(), 'period_id' => $this->periodId(), 'description' => 'test', 'source' => 'manual'],
            [
                ['account_id' => $this->cashAccountId, 'debit_amount' => 500, 'credit_amount' => 0, 'description' => 'DR'],
                ['account_id' => $this->revenueAccountId, 'debit_amount' => 0, 'credit_amount' => 500, 'description' => 'CR'],
            ]
        );

        $balance = JournalService::getAccountBalance($this->cashAccountId);

        $this->assertEquals(500, $balance['debit']);
        $this->assertEquals(0, $balance['credit']);
        $this->assertEquals(500, $balance['balance']);
        $this->assertEquals('debit', $balance['normal_balance']);
    }

    public function test_getAccountBalance_credit_normal(): void
    {
        JournalService::createJournal(
            ['date' => $this->today(), 'period_id' => $this->periodId(), 'description' => 'test', 'source' => 'manual'],
            [
                ['account_id' => $this->cashAccountId, 'debit_amount' => 500, 'credit_amount' => 0, 'description' => 'DR'],
                ['account_id' => $this->revenueAccountId, 'debit_amount' => 0, 'credit_amount' => 500, 'description' => 'CR'],
            ]
        );

        $balance = JournalService::getAccountBalance($this->revenueAccountId);

        $this->assertEquals(0, $balance['debit']);
        $this->assertEquals(500, $balance['credit']);
        $this->assertEquals(500, $balance['balance']);
        $this->assertEquals('credit', $balance['normal_balance']);
    }

    public function test_getAccountBalance_excludes_void_journals(): void
    {
        $journal = JournalService::createJournal(
            ['date' => $this->today(), 'period_id' => $this->periodId(), 'description' => 'Original', 'source' => 'manual'],
            [
                ['account_id' => $this->cashAccountId, 'debit_amount' => 500, 'credit_amount' => 0, 'description' => 'DR'],
                ['account_id' => $this->revenueAccountId, 'debit_amount' => 0, 'credit_amount' => 500, 'description' => 'CR'],
            ]
        );

        JournalService::voidJournal($journal, 'Cancel');

        $balance = JournalService::getAccountBalance($this->cashAccountId);
        $this->assertEquals(-500, $balance['balance']);
    }

    public function test_getAccountBalance_filter_by_period(): void
    {
        JournalService::createJournal(
            ['date' => $this->today(), 'period_id' => $this->periodId(), 'description' => 'test', 'source' => 'manual'],
            [
                ['account_id' => $this->cashAccountId, 'debit_amount' => 100, 'credit_amount' => 0, 'description' => 'DR'],
                ['account_id' => $this->revenueAccountId, 'debit_amount' => 0, 'credit_amount' => 100, 'description' => 'CR'],
            ]
        );

        $balance = JournalService::getAccountBalance($this->cashAccountId, $this->periodId());
        $this->assertEquals(100, $balance['balance']);
    }

    public function test_getAccountBalance_with_multiple_transactions(): void
    {
        for ($i = 0; $i < 3; $i++) {
            JournalService::createJournal(
                ['date' => $this->today(), 'period_id' => $this->periodId(), 'description' => "tx $i", 'source' => 'manual'],
                [
                    ['account_id' => $this->cashAccountId, 'debit_amount' => 100, 'credit_amount' => 0, 'description' => 'DR'],
                    ['account_id' => $this->revenueAccountId, 'debit_amount' => 0, 'credit_amount' => 100, 'description' => 'CR'],
                ]
            );
        }

        $balance = JournalService::getAccountBalance($this->cashAccountId);
        $this->assertEquals(300, $balance['balance']);
    }

    // ==================== getTrialBalance ====================

    public function test_getTrialBalance_returns_accounts(): void
    {
        JournalService::createJournal(
            ['date' => $this->today(), 'period_id' => $this->periodId(), 'description' => 'test', 'source' => 'manual'],
            [
                ['account_id' => $this->cashAccountId, 'debit_amount' => 100, 'credit_amount' => 0, 'description' => 'DR'],
                ['account_id' => $this->revenueAccountId, 'debit_amount' => 0, 'credit_amount' => 100, 'description' => 'CR'],
            ]
        );

        $trialBalance = JournalService::getTrialBalance($this->periodId());

        $this->assertGreaterThan(0, $trialBalance->count());
        $cashResult = $trialBalance->firstWhere('account.id', $this->cashAccountId);
        $this->assertNotNull($cashResult);
        $this->assertEquals(100, $cashResult['balance']);
    }

    // ==================== getAccountBalanceUpTo / getAccountBalanceChange ====================

    public function test_getAccountBalanceUpToDate(): void
    {
        $todayStr = now()->format('Y-m-d');
        JournalService::createJournal(
            ['date' => $todayStr, 'period_id' => $this->periodId(), 'description' => 'test', 'source' => 'manual'],
            [
                ['account_id' => $this->cashAccountId, 'debit_amount' => 100, 'credit_amount' => 0, 'description' => 'DR'],
                ['account_id' => $this->revenueAccountId, 'debit_amount' => 0, 'credit_amount' => 100, 'description' => 'CR'],
            ]
        );

        // use day after to include journals stored with time in SQLite
        $dayAfter = now()->addDay()->format('Y-m-d');
        $balance = JournalService::getAccountBalanceUpToDate($this->cashAccountId, $dayAfter);
        $this->assertEquals(100, $balance['balance']);

        $yesterday = Carbon::yesterday()->format('Y-m-d');
        $balanceBefore = JournalService::getAccountBalanceUpToDate($this->cashAccountId, $yesterday);
        $this->assertEquals(0, $balanceBefore['balance']);
    }

    public function test_getAccountBalanceChange(): void
    {
        $today = $this->today();
        JournalService::createJournal(
            ['date' => $today, 'period_id' => $this->periodId(), 'description' => 'test', 'source' => 'manual'],
            [
                ['account_id' => $this->cashAccountId, 'debit_amount' => 100, 'credit_amount' => 0, 'description' => 'DR'],
                ['account_id' => $this->revenueAccountId, 'debit_amount' => 0, 'credit_amount' => 100, 'description' => 'CR'],
            ]
        );

        // use day-after to overcome SQLite date+time storage issue
        $dayAfter = now()->addDay()->format('Y-m-d');
        $change = JournalService::getAccountBalanceChange($this->cashAccountId, $today, $dayAfter);
        $this->assertEquals(100, $change);
    }

    // ==================== getIncomeStatement ====================

    public function test_getIncomeStatement_returns_structure(): void
    {
        JournalService::createJournal(
            ['date' => $this->today(), 'period_id' => $this->periodId(), 'description' => 'Revenue', 'source' => 'sale'],
            [
                ['account_id' => $this->cashAccountId, 'debit_amount' => 1000, 'credit_amount' => 0, 'description' => 'DR'],
                ['account_id' => $this->revenueAccountId, 'debit_amount' => 0, 'credit_amount' => 1000, 'description' => 'CR'],
            ]
        );

        $result = JournalService::getIncomeStatement($this->periodId());

        $this->assertArrayHasKey('total_revenue', $result);
        $this->assertArrayHasKey('total_cogs', $result);
        $this->assertArrayHasKey('gross_profit', $result);
        $this->assertArrayHasKey('total_expenses', $result);
        $this->assertArrayHasKey('net_income', $result);
        $this->assertArrayHasKey('period_label', $result);
        $this->assertGreaterThan(0, $result['total_revenue']);
    }

    // ==================== getBalanceSheet ====================

    public function test_getBalanceSheet_returns_structure(): void
    {
        $result = JournalService::getBalanceSheet($this->periodId());

        $this->assertArrayHasKey('assets', $result);
        $this->assertArrayHasKey('liabilities', $result);
        $this->assertArrayHasKey('equity', $result);
        $this->assertArrayHasKey('is_balanced', $result);
        $this->assertArrayHasKey('as_of_date', $result);
        $this->assertIsBool($result['is_balanced']);
    }

    // ==================== getAgingReport ====================

    public function test_getAgingReport_returns_structure(): void
    {
        $result = JournalService::getAgingReport('receivable');

        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('totals', $result);
        $this->assertArrayHasKey('grand_total', $result);
        $this->assertArrayHasKey('as_of_date', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertEquals('receivable', $result['type']);
    }

    // ==================== getCashInOut ====================

    public function test_getCashInOut_returns_structure(): void
    {
        // create wallets so the method returns full structure
        $this->createWallet(['name' => 'Cash In Test', 'opening_balance' => 0]);

        $result = JournalService::getCashInOut('2025-01-01', '2025-12-31');

        $this->assertArrayHasKey('cash_in', $result);
        $this->assertArrayHasKey('cash_in_total', $result);
        $this->assertArrayHasKey('cash_out', $result);
        $this->assertArrayHasKey('cash_out_total', $result);
        $this->assertArrayHasKey('net', $result);
    }
}
