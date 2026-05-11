<?php

namespace Tests\Feature;

use App\Exceptions\AccountingImbalanceException;
use App\Exceptions\InvalidStateException;
use App\Exceptions\PeriodClosedException;
use App\Models\Account;
use App\Models\Period;
use App\Services\BaseTransactionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\Helpers\TestSetup;
use Tests\TestCase;

class BaseTransactionServiceTest extends TestCase
{
    use RefreshDatabase, TestSetup;

    private BaseTransactionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestDatabase();

        $this->service = new class extends BaseTransactionService {
            public function publicValidatePeriodOpen(Carbon $date): void
            {
                $this->validatePeriodOpen($date);
            }

            public function publicValidateNotPosted($model): void
            {
                $this->validateNotPosted($model);
            }

            public function publicValidateNotCancelled($model): void
            {
                $this->validateNotCancelled($model);
            }

            public function publicCreateJournalEntry(array $data, array $lines)
            {
                return $this->createJournalEntry($data, $lines);
            }
        };
    }

    public function test_validatePeriodOpen_with_open_period_passes(): void
    {
        $date = Carbon::parse($this->currentPeriod->start_date);
        $this->service->publicValidatePeriodOpen($date);
        $this->assertTrue(true);
    }

    public function test_validatePeriodOpen_with_closed_period_throws(): void
    {
        $this->expectException(PeriodClosedException::class);

        $closedPeriod = Period::create([
            'name' => 'Closed Period',
            'start_date' => '2020-01-01',
            'end_date' => '2020-01-31',
            'is_closed' => true,
        ]);

        $this->service->publicValidatePeriodOpen(Carbon::parse('2020-01-15'));
    }

    public function test_validateNotPosted_with_draft_passes(): void
    {
        $model = Account::factory()->create();
        $model->status = 'draft';
        $this->service->publicValidateNotPosted($model);
        $this->assertTrue(true);
    }

    public function test_validateNotPosted_with_posted_throws(): void
    {
        $this->expectException(InvalidStateException::class);

        $model = Account::factory()->create();
        $model->status = 'posted';
        $this->service->publicValidateNotPosted($model);
    }

    public function test_validateNotCancelled_with_draft_passes(): void
    {
        $model = Account::factory()->create();
        $model->status = 'draft';
        $this->service->publicValidateNotCancelled($model);
        $this->assertTrue(true);
    }

    public function test_validateNotCancelled_with_cancelled_throws(): void
    {
        $this->expectException(InvalidStateException::class);

        $model = Account::factory()->create();
        $model->status = 'cancelled';
        $this->service->publicValidateNotCancelled($model);
    }

    public function test_createJournalEntry_with_less_than_2_lines_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->publicCreateJournalEntry(
            ['date' => $this->today(), 'period_id' => $this->periodId(), 'description' => 'test'],
            [['account_id' => 1, 'debit_amount' => 100, 'credit_amount' => 0]]
        );
    }

    public function test_createJournalEntry_with_debit_and_credit_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->publicCreateJournalEntry(
            ['date' => $this->today(), 'period_id' => $this->periodId(), 'description' => 'test'],
            [
                ['account_id' => 1, 'debit_amount' => 100, 'credit_amount' => 50, 'description' => 'Invalid line'],
                ['account_id' => 2, 'debit_amount' => 0, 'credit_amount' => 150, 'description' => 'other'],
            ]
        );
    }

    public function test_createJournalEntry_with_zero_values_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->publicCreateJournalEntry(
            ['date' => $this->today(), 'period_id' => $this->periodId(), 'description' => 'test'],
            [
                ['account_id' => 1, 'debit_amount' => 0, 'credit_amount' => 0, 'description' => 'Zero line'],
                ['account_id' => 2, 'debit_amount' => 100, 'credit_amount' => 0, 'description' => 'other'],
            ]
        );
    }

    public function test_createJournalEntry_with_negative_values_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->publicCreateJournalEntry(
            ['date' => $this->today(), 'period_id' => $this->periodId(), 'description' => 'test'],
            [
                ['account_id' => 1, 'debit_amount' => -100, 'credit_amount' => 0, 'description' => 'Negative'],
                ['account_id' => 2, 'debit_amount' => 0, 'credit_amount' => 100, 'description' => 'other'],
            ]
        );
    }

    public function test_createJournalEntry_with_imbalance_throws(): void
    {
        $this->expectException(AccountingImbalanceException::class);

        $account1 = $this->getAccountId('1100-00-010');
        $account2 = $this->getAccountId('4100-00-010');

        $this->service->publicCreateJournalEntry(
            ['date' => $this->today(), 'period_id' => $this->periodId(), 'description' => 'test'],
            [
                ['account_id' => $account1, 'debit_amount' => 100, 'credit_amount' => 0, 'description' => 'DR'],
                ['account_id' => $account2, 'debit_amount' => 0, 'credit_amount' => 99, 'description' => 'CR'],
            ]
        );
    }

    public function test_createJournalEntry_with_balanced_lines_succeeds(): void
    {
        $account1 = $this->getAccountId('1100-00-010');
        $account2 = $this->getAccountId('4100-00-010');

        $journal = $this->service->publicCreateJournalEntry(
            ['date' => $this->today(), 'period_id' => $this->periodId(), 'description' => 'Test entry', 'source' => 'manual'],
            [
                ['account_id' => $account1, 'debit_amount' => 100, 'credit_amount' => 0, 'description' => 'DR test'],
                ['account_id' => $account2, 'debit_amount' => 0, 'credit_amount' => 100, 'description' => 'CR test'],
            ]
        );

        $this->assertNotNull($journal);
        $this->assertEquals(2, $journal->lines->count());
        $this->assertEquals(100, (float) $journal->lines->sum('debit_amount'));
        $this->assertEquals(100, (float) $journal->lines->sum('credit_amount'));
    }
}
