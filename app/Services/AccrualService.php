<?php

namespace App\Services;

use App\Exceptions\PeriodClosedException;
use App\Models\Account;
use App\Models\DeferredRevenue;
use App\Models\Journal;
use App\Models\Period;
use App\Models\PrepaidExpense;
use App\Models\Wallet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccrualService
{
    // ==================== ACCRUED EXPENSES ====================

    /**
     * Create accrued expense journal: Debit Expense, Credit Accrued Liability.
     */
    public static function createAccruedExpense(
        Period $period,
        Account $expenseAccount,
        Account $liabilityAccount,
        float $amount,
        string $description,
        ?int $userId = null
    ): Journal {
        if ($period->is_closed) {
            throw new PeriodClosedException();
        }

        $journalLines = [
            [
                'account_id' => $expenseAccount->id,
                'debit_amount' => $amount,
                'credit_amount' => 0,
            ],
            [
                'account_id' => $liabilityAccount->id,
                'debit_amount' => 0,
                'credit_amount' => $amount,
            ],
        ];

        return JournalService::createJournal([
            'date' => $period->end_date,
            'period_id' => $period->id,
            'description' => 'Akrual Beban: ' . $description,
            'source' => 'system',
            'type' => 'normal',
            'created_by' => $userId ?? Auth::id(),
        ], $journalLines);
    }

    /**
     * Reverse an accrued expense in the next period.
     */
    public static function reverseAccruedExpense(
        Journal $journal,
        Period $nextPeriod,
        string $reason,
        ?int $userId = null
    ): Journal {
        if ($nextPeriod->is_closed) {
            throw new PeriodClosedException();
        }

        $reversalLines = $journal->lines->map(function ($line) {
            return [
                'account_id' => $line->account_id,
                'debit_amount' => $line->credit_amount,
                'credit_amount' => $line->debit_amount,
            ];
        })->toArray();

        return JournalService::createJournal([
            'date' => $nextPeriod->start_date,
            'period_id' => $nextPeriod->id,
            'description' => 'Reversal Akrual: ' . $reason,
            'source' => 'system',
            'type' => 'reversal',
            'created_by' => $userId ?? Auth::id(),
        ], $reversalLines);
    }

    // ==================== ACCRUED REVENUE ====================

    /**
     * Create accrued revenue journal: Debit Receivable, Credit Revenue.
     */
    public static function createAccruedRevenue(
        Period $period,
        Account $receivableAccount,
        Account $revenueAccount,
        float $amount,
        string $description,
        ?int $userId = null
    ): Journal {
        if ($period->is_closed) {
            throw new PeriodClosedException();
        }

        $journalLines = [
            [
                'account_id' => $receivableAccount->id,
                'debit_amount' => $amount,
                'credit_amount' => 0,
            ],
            [
                'account_id' => $revenueAccount->id,
                'debit_amount' => 0,
                'credit_amount' => $amount,
            ],
        ];

        return JournalService::createJournal([
            'date' => $period->end_date,
            'period_id' => $period->id,
            'description' => 'Akrual Pendapatan: ' . $description,
            'source' => 'system',
            'type' => 'normal',
            'created_by' => $userId ?? Auth::id(),
        ], $journalLines);
    }

    // ==================== PREPAID EXPENSES ====================

    /**
     * Create a prepaid expense: record + initial journal.
     * Journal: Debit AssetAccount, Credit WalletAccount.
     */
    public static function createPrepaidExpense(
        Period $period,
        Account $assetAccount,
        Account $expenseAccount,
        Wallet $wallet,
        float $totalAmount,
        int $totalMonths,
        string $description,
        ?int $userId = null
    ): PrepaidExpense {
        if ($period->is_closed) {
            throw new PeriodClosedException();
        }

        $monthlyAmount = $totalAmount / max(1, $totalMonths);

        return DB::transaction(function () use ($period, $assetAccount, $expenseAccount, $wallet, $totalAmount, $totalMonths, $monthlyAmount, $description, $userId) {
            $journalLines = [
                [
                    'account_id' => $assetAccount->id,
                    'debit_amount' => $totalAmount,
                    'credit_amount' => 0,
                ],
                [
                    'account_id' => $wallet->account_id,
                    'debit_amount' => 0,
                    'credit_amount' => $totalAmount,
                    'wallet_id' => $wallet->id,
                ],
            ];

            $journal = JournalService::createJournal([
                'date' => $period->start_date,
                'period_id' => $period->id,
                'description' => 'Pembayaran Dimuka: ' . $description,
                'source' => 'system',
                'type' => 'normal',
                'created_by' => $userId ?? Auth::id(),
            ], $journalLines);

            return PrepaidExpense::create([
                'period_id' => $period->id,
                'asset_account_id' => $assetAccount->id,
                'expense_account_id' => $expenseAccount->id,
                'description' => $description,
                'total_amount' => $totalAmount,
                'remaining_amount' => $totalAmount,
                'start_date' => $period->start_date,
                'total_months' => $totalMonths,
                'months_amortized' => 0,
                'monthly_amount' => $monthlyAmount,
                'status' => 'active',
                'created_by' => $userId ?? Auth::id(),
                'journal_id' => $journal->id,
            ]);
        });
    }

    /**
     * Amortize one month of a prepaid expense.
     * Journal: Debit ExpenseAccount, Credit AssetAccount.
     */
    public static function amortizePrepaid(
        PrepaidExpense $prepaid,
        Period $period,
        ?int $userId = null
    ): Journal {
        if ($prepaid->status !== 'active') {
            throw new \Exception('Prepaid sudah diamortisasi penuh atau dibatalkan.');
        }

        if ($period->is_closed) {
            throw new PeriodClosedException();
        }

        $amount = min((float) $prepaid->monthly_amount, (float) $prepaid->remaining_amount);

        if ($amount <= 0) {
            throw new \Exception('Tidak ada sisa yang perlu diamortisasi.');
        }

        return DB::transaction(function () use ($prepaid, $period, $amount, $userId) {
            $journalLines = [
                [
                    'account_id' => $prepaid->expense_account_id,
                    'debit_amount' => $amount,
                    'credit_amount' => 0,
                ],
                [
                    'account_id' => $prepaid->asset_account_id,
                    'debit_amount' => 0,
                    'credit_amount' => $amount,
                ],
            ];

            $journal = JournalService::createJournal([
                'date' => $period->end_date,
                'period_id' => $period->id,
                'description' => 'Amortisasi Prepaid: ' . $prepaid->description,
                'source' => 'system',
                'type' => 'normal',
                'ref_type' => 'prepaid_amortization',
                'ref_id' => $prepaid->id,
                'created_by' => $userId ?? Auth::id(),
            ], $journalLines);

            $newMonthsAmortized = $prepaid->months_amortized + 1;
            $newRemaining = (float) $prepaid->remaining_amount - $amount;
            $isFullyAmortized = $newRemaining <= 0 || $newMonthsAmortized >= $prepaid->total_months;

            $prepaid->update([
                'months_amortized' => $newMonthsAmortized,
                'remaining_amount' => max(0, $newRemaining),
                'status' => $isFullyAmortized ? 'fully_amortized' : 'active',
            ]);

            return $journal;
        });
    }

    /**
     * Amortize all active prepaids for a period.
     */
    public static function amortizeAllPrepaids(Period $period, ?int $userId = null): array
    {
        $prepaids = PrepaidExpense::active()->get();
        $results = ['success' => [], 'failed' => []];

        foreach ($prepaids as $prepaid) {
            $alreadyAmortized = Journal::where('ref_type', 'prepaid_amortization')
                ->where('ref_id', $prepaid->id)
                ->where('period_id', $period->id)
                ->where('type', '!=', 'void')
                ->exists();

            if ($alreadyAmortized) {
                $results['failed'][] = [
                    'id' => $prepaid->id,
                    'description' => $prepaid->description,
                    'error' => 'Sudah diamortisasi untuk periode ini',
                ];
                continue;
            }

            try {
                $journal = self::amortizePrepaid($prepaid, $period, $userId);
                $results['success'][] = [
                    'id' => $prepaid->id,
                    'description' => $prepaid->description,
                    'amount' => $prepaid->monthly_amount,
                    'journal_id' => $journal->id,
                ];
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'id' => $prepaid->id,
                    'description' => $prepaid->description,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    // ==================== DEFERRED REVENUE ====================

    /**
     * Create a deferred revenue: record + initial journal.
     * Journal: Debit WalletAccount, Credit LiabilityAccount.
     */
    public static function createDeferredRevenue(
        Period $period,
        Account $liabilityAccount,
        Account $revenueAccount,
        Wallet $wallet,
        float $totalAmount,
        int $totalMonths,
        string $description,
        ?int $userId = null
    ): DeferredRevenue {
        if ($period->is_closed) {
            throw new PeriodClosedException();
        }

        $monthlyAmount = $totalAmount / max(1, $totalMonths);

        return DB::transaction(function () use ($period, $liabilityAccount, $revenueAccount, $wallet, $totalAmount, $totalMonths, $monthlyAmount, $description, $userId) {
            $journalLines = [
                [
                    'account_id' => $wallet->account_id,
                    'debit_amount' => $totalAmount,
                    'credit_amount' => 0,
                    'wallet_id' => $wallet->id,
                ],
                [
                    'account_id' => $liabilityAccount->id,
                    'debit_amount' => 0,
                    'credit_amount' => $totalAmount,
                ],
            ];

            $journal = JournalService::createJournal([
                'date' => $period->start_date,
                'period_id' => $period->id,
                'description' => 'Pendapatan Diterima Dimuka: ' . $description,
                'source' => 'system',
                'type' => 'normal',
                'created_by' => $userId ?? Auth::id(),
            ], $journalLines);

            return DeferredRevenue::create([
                'period_id' => $period->id,
                'liability_account_id' => $liabilityAccount->id,
                'revenue_account_id' => $revenueAccount->id,
                'description' => $description,
                'total_amount' => $totalAmount,
                'remaining_amount' => $totalAmount,
                'start_date' => $period->start_date,
                'total_months' => $totalMonths,
                'months_recognized' => 0,
                'monthly_amount' => $monthlyAmount,
                'status' => 'active',
                'created_by' => $userId ?? Auth::id(),
                'journal_id' => $journal->id,
            ]);
        });
    }

    /**
     * Recognize one month of deferred revenue.
     * Journal: Debit LiabilityAccount, Credit RevenueAccount.
     */
    public static function recognizeDeferred(
        DeferredRevenue $deferred,
        Period $period,
        ?int $userId = null
    ): Journal {
        if ($deferred->status !== 'active') {
            throw new \Exception('Deferred revenue sudah diakui penuh atau dibatalkan.');
        }

        if ($period->is_closed) {
            throw new PeriodClosedException();
        }

        $amount = min((float) $deferred->monthly_amount, (float) $deferred->remaining_amount);

        if ($amount <= 0) {
            throw new \Exception('Tidak ada sisa yang perlu diakui.');
        }

        return DB::transaction(function () use ($deferred, $period, $amount, $userId) {
            $journalLines = [
                [
                    'account_id' => $deferred->liability_account_id,
                    'debit_amount' => $amount,
                    'credit_amount' => 0,
                ],
                [
                    'account_id' => $deferred->revenue_account_id,
                    'debit_amount' => 0,
                    'credit_amount' => $amount,
                ],
            ];

            $journal = JournalService::createJournal([
                'date' => $period->end_date,
                'period_id' => $period->id,
                'description' => 'Pengakuan Pendapatan Dimuka: ' . $deferred->description,
                'source' => 'system',
                'type' => 'normal',
                'ref_type' => 'deferred_recognition',
                'ref_id' => $deferred->id,
                'created_by' => $userId ?? Auth::id(),
            ], $journalLines);

            $newMonthsRecognized = $deferred->months_recognized + 1;
            $newRemaining = (float) $deferred->remaining_amount - $amount;
            $isFullyRecognized = $newRemaining <= 0 || $newMonthsRecognized >= $deferred->total_months;

            $deferred->update([
                'months_recognized' => $newMonthsRecognized,
                'remaining_amount' => max(0, $newRemaining),
                'status' => $isFullyRecognized ? 'fully_recognized' : 'active',
            ]);

            return $journal;
        });
    }

    /**
     * Recognize all active deferred revenues for a period.
     */
    public static function recognizeAllDeferred(Period $period, ?int $userId = null): array
    {
        $deferreds = DeferredRevenue::active()->get();
        $results = ['success' => [], 'failed' => []];

        foreach ($deferreds as $deferred) {
            $alreadyRecognized = Journal::where('ref_type', 'deferred_recognition')
                ->where('ref_id', $deferred->id)
                ->where('period_id', $period->id)
                ->where('type', '!=', 'void')
                ->exists();

            if ($alreadyRecognized) {
                continue;
            }

            try {
                $journal = self::recognizeDeferred($deferred, $period, $userId);
                $results['success'][] = [
                    'id' => $deferred->id,
                    'description' => $deferred->description,
                    'amount' => $deferred->monthly_amount,
                    'journal_id' => $journal->id,
                ];
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'id' => $deferred->id,
                    'description' => $deferred->description,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    // ==================== MASS PROCESSING ====================

    /**
     * Run all monthly accruals for a period:
     * - Amortize all active prepaids
     * - Recognize all active deferred revenues
     */
    public static function createMonthlyAccruals(Period $period, ?int $userId = null): array
    {
        $prepaidResults = self::amortizeAllPrepaids($period, $userId);
        $deferredResults = self::recognizeAllDeferred($period, $userId);

        return [
            'prepaid' => $prepaidResults,
            'deferred' => $deferredResults,
        ];
    }
}
