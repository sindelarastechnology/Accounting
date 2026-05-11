<?php

namespace Tests\Feature;

use App\Exceptions\InvalidAccountException;
use App\Models\Account;
use App\Models\TaxRule;
use App\Services\ExpenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\TestSetup;
use Tests\TestCase;

class ExpenseServiceTest extends TestCase
{
    use RefreshDatabase, TestSetup;

    private $wallet;
    private $expenseAccount;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestDatabase();

        $this->wallet = $this->createWallet();
        $this->expenseAccount = Account::find($this->getAccountId('6110-00-010'));
    }

    // ==================== createExpense ====================

    public function test_createExpense_creates_draft(): void
    {
        $expense = ExpenseService::createExpense([
            'period_id' => $this->periodId(),
            'date' => $this->today(),
            'name' => 'Listrik',
            'account_id' => $this->expenseAccount->id,
            'wallet_id' => $this->wallet->id,
            'amount' => 500000,
        ]);

        $this->assertEquals('draft', $expense->status);
        $this->assertEquals(500000, (float) $expense->amount);
        $this->assertEquals('Listrik', $expense->name);
    }

    // ==================== postExpense ====================

    public function test_postExpense_without_tax_creates_journal(): void
    {
        $expense = ExpenseService::createExpense([
            'period_id' => $this->periodId(),
            'date' => $this->today(),
            'name' => 'Listrik',
            'account_id' => $this->expenseAccount->id,
            'wallet_id' => $this->wallet->id,
            'amount' => 500000,
        ]);

        $journal = ExpenseService::postExpense($expense);

        $this->assertNotNull($journal);
        $expense->refresh();
        $this->assertEquals('posted', $expense->status);

        $cashAccountId = $this->getAccountId('1100-00-010');
        $this->assertEquals(500000, (float) $journal->lines->firstWhere('account_id', $this->expenseAccount->id)->debit_amount);
        $this->assertEquals(500000, (float) $journal->lines->firstWhere('account_id', $cashAccountId)->credit_amount);
    }

    public function test_postExpense_with_ppn_inclusive_splits_tax(): void
    {
        $expense = ExpenseService::createExpense([
            'period_id' => $this->periodId(),
            'date' => $this->today(),
            'name' => 'Listrik Include PPN',
            'account_id' => $this->expenseAccount->id,
            'wallet_id' => $this->wallet->id,
            'amount' => 111000,
            'include_tax' => true,
        ]);

        $journal = ExpenseService::postExpense($expense);

        $this->assertNotNull($journal);
        // total = DR expense (DPP) + DR PPN, CR cash = total
        $this->assertEquals(111000, (float) $journal->lines->sum('debit_amount'));
        $this->assertEquals(111000, (float) $journal->lines->sum('credit_amount'));
        $this->assertEquals(3, $journal->lines->count());
    }

    public function test_postExpense_draft_status_required(): void
    {
        $expense = ExpenseService::createExpense([
            'period_id' => $this->periodId(),
            'date' => $this->today(),
            'name' => 'Test',
            'account_id' => $this->expenseAccount->id,
            'wallet_id' => $this->wallet->id,
            'amount' => 100000,
        ]);

        ExpenseService::postExpense($expense);

        $this->expectException(InvalidAccountException::class);
        ExpenseService::postExpense($expense);
    }

    // ==================== cancelExpense ====================

    public function test_cancelExpense(): void
    {
        $expense = ExpenseService::createExpense([
            'period_id' => $this->periodId(),
            'date' => $this->today(),
            'name' => 'Test',
            'account_id' => $this->expenseAccount->id,
            'wallet_id' => $this->wallet->id,
            'amount' => 100000,
        ]);
        ExpenseService::postExpense($expense);
        ExpenseService::cancelExpense($expense);

        $expense->refresh();
        $this->assertEquals('cancelled', $expense->status);
    }

    public function test_cancelExpense_already_cancelled_throws(): void
    {
        $expense = ExpenseService::createExpense([
            'period_id' => $this->periodId(),
            'date' => $this->today(),
            'name' => 'Test',
            'account_id' => $this->expenseAccount->id,
            'wallet_id' => $this->wallet->id,
            'amount' => 100000,
        ]);
        ExpenseService::cancelExpense($expense);

        $this->expectException(InvalidAccountException::class);
        ExpenseService::cancelExpense($expense);
    }
}
