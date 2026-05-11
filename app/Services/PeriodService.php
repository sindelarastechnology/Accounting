<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Journal;
use App\Models\OpeningBalance;
use App\Models\Period;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PeriodService
{
    public static function closePeriod(Period $period, int $userId): Period
    {
        if ($period->is_closed) {
            throw new \App\Exceptions\PeriodClosedException('Periode sudah ditutup.');
        }

        $hasDraftJournals = Journal::where('period_id', $period->id)
            ->where('type', 'draft')
            ->exists();
        if ($hasDraftJournals) {
            throw new \App\Exceptions\PeriodClosedException('Masih ada jurnal draft di periode ini. Posting atau hapus terlebih dahulu.');
        }

        $hasDraftInvoices = DB::table('invoices')
            ->where('status', 'draft')
            ->where('date', '>=', $period->start_date)
            ->where('date', '<=', $period->end_date)
            ->exists();
        if ($hasDraftInvoices) {
            throw new \App\Exceptions\PeriodClosedException('Masih ada invoice draft di periode ini.');
        }

        $hasDraftPurchases = DB::table('purchases')
            ->where('status', 'draft')
            ->where('date', '>=', $period->start_date)
            ->where('date', '<=', $period->end_date)
            ->exists();
        if ($hasDraftPurchases) {
            throw new \App\Exceptions\PeriodClosedException('Masih ada purchase order draft di periode ini.');
        }

        $hasDraftCreditNotes = DB::table('credit_notes')
            ->where('status', 'draft')
            ->where('date', '>=', $period->start_date)
            ->where('date', '<=', $period->end_date)
            ->exists();
        if ($hasDraftCreditNotes) {
            throw new \App\Exceptions\PeriodClosedException('Masih ada credit note draft di periode ini.');
        }

        $hasDraftDebitNotes = DB::table('debit_notes')
            ->where('status', 'draft')
            ->where('date', '>=', $period->start_date)
            ->where('date', '<=', $period->end_date)
            ->exists();
        if ($hasDraftDebitNotes) {
            throw new \App\Exceptions\PeriodClosedException('Masih ada debit note draft di periode ini.');
        }

        $hasDraftExpenses = DB::table('expenses')
            ->where('status', 'draft')
            ->where('date', '>=', $period->start_date)
            ->where('date', '<=', $period->end_date)
            ->exists();
        if ($hasDraftExpenses) {
            throw new \App\Exceptions\PeriodClosedException('Masih ada expense draft di periode ini.');
        }

        $hasDraftFundTransfers = DB::table('fund_transfers')
            ->where('status', 'draft')
            ->where('date', '>=', $period->start_date)
            ->where('date', '<=', $period->end_date)
            ->exists();
        if ($hasDraftFundTransfers) {
            throw new \App\Exceptions\PeriodClosedException('Masih ada fund transfer draft di periode ini.');
        }

        $hasDraftOtherReceipts = DB::table('other_receipts')
            ->where('status', 'draft')
            ->where('date', '>=', $period->start_date)
            ->where('date', '<=', $period->end_date)
            ->exists();
        if ($hasDraftOtherReceipts) {
            throw new \App\Exceptions\PeriodClosedException('Masih ada other receipt draft di periode ini.');
        }

        $hasDraftTaxPayments = DB::table('tax_payments')
            ->where('status', 'draft')
            ->where('payment_date', '>=', $period->start_date)
            ->where('payment_date', '<=', $period->end_date)
            ->exists();
        if ($hasDraftTaxPayments) {
            throw new \App\Exceptions\PeriodClosedException('Masih ada tax payment draft di periode ini.');
        }

        $incomeSummaryId = Setting::get('income_summary_id');
        $retainedEarningsId = Setting::get('retained_earnings_id');

        if (!$incomeSummaryId) {
            throw new \Exception('Akun Ikhtisar Laba Rugi belum diatur di Settings.');
        }
        if (!$retainedEarningsId) {
            throw new \Exception('Akun Laba Ditahan belum diatur di Settings.');
        }

        DB::beginTransaction();
        try {
            $revenueAccounts = Account::where('is_header', false)
                ->where('is_active', true)
                ->where('category', 'revenue')
                ->get();

            $totalRevenue = 0;
            $journalLines = [];

            foreach ($revenueAccounts as $account) {
                $balance = JournalService::getAccountBalance($account->id, $period->id);
                $accountBalance = $balance['balance'];

                if ($accountBalance > 0) {
                    $journalLines[] = [
                        'account_id' => $account->id,
                        'debit_amount' => $accountBalance,
                        'credit_amount' => 0,
                    ];
                    $journalLines[] = [
                        'account_id' => $incomeSummaryId,
                        'debit_amount' => 0,
                        'credit_amount' => $accountBalance,
                    ];
                    $totalRevenue += $accountBalance;
                }
            }

            $expenseAccounts = Account::where('is_header', false)
                ->where('is_active', true)
                ->whereIn('category', ['cogs', 'expense'])
                ->get();

            $totalExpenses = 0;

            foreach ($expenseAccounts as $account) {
                $balance = JournalService::getAccountBalance($account->id, $period->id);
                $accountBalance = $balance['balance'];

                if ($accountBalance > 0) {
                    $journalLines[] = [
                        'account_id' => $incomeSummaryId,
                        'debit_amount' => $accountBalance,
                        'credit_amount' => 0,
                    ];
                    $journalLines[] = [
                        'account_id' => $account->id,
                        'debit_amount' => 0,
                        'credit_amount' => $accountBalance,
                    ];
                    $totalExpenses += $accountBalance;
                }
            }

            $netIncome = $totalRevenue - $totalExpenses;

            if ($netIncome != 0) {
                if ($netIncome > 0) {
                    $journalLines[] = [
                        'account_id' => $incomeSummaryId,
                        'debit_amount' => $netIncome,
                        'credit_amount' => 0,
                    ];
                    $journalLines[] = [
                        'account_id' => $retainedEarningsId,
                        'debit_amount' => 0,
                        'credit_amount' => $netIncome,
                    ];
                } else {
                    $absNetIncome = abs($netIncome);
                    $journalLines[] = [
                        'account_id' => $retainedEarningsId,
                        'debit_amount' => $absNetIncome,
                        'credit_amount' => 0,
                    ];
                    $journalLines[] = [
                        'account_id' => $incomeSummaryId,
                        'debit_amount' => 0,
                        'credit_amount' => $absNetIncome,
                    ];
                }
            }

            if (count($journalLines) >= 2) {
                JournalService::createJournal([
                    'date' => $period->end_date,
                    'period_id' => $period->id,
                    'description' => "Jurnal Penutup — {$period->name}",
                    'source' => 'system',
                    'type' => 'closing',
                    'created_by' => $userId,
                ], $journalLines);
            }

            $period->update([
                'is_closed' => true,
                'closed_at' => now(),
                'closed_by' => $userId,
            ]);

            DB::commit();

            try {
                $nextPeriod = static::getNextPeriod($period);
                if (!$nextPeriod) {
                    $nextPeriod = static::createNextPeriodUnsafe($period);
                }
                if ($nextPeriod) {
                    static::carryForwardBalances($period, $nextPeriod);
                }
            } catch (\Exception $e) {
                Log::warning('Auto carry forward gagal (periode tetap ditutup): ' . $e->getMessage());
            }

            return $period->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public static function reopenPeriod(Period $period, int $userId, string $reason): Period
    {
        if (!$period->is_closed) {
            throw new \Exception('Periode ini belum ditutup.');
        }

        $laterClosedPeriod = Period::where('is_closed', true)
            ->where('start_date', '>', $period->start_date)
            ->exists();

        if ($laterClosedPeriod) {
            throw new \Exception('Tidak dapat membuka kembali periode karena ada periode yang lebih baru sudah ditutup. Buka terlebih dahulu periode yang lebih baru.');
        }

        DB::beginTransaction();
        try {
            $closingJournals = Journal::where('period_id', $period->id)
                ->where('type', 'closing')
                ->where('source', 'system')
                ->get();

            foreach ($closingJournals as $journal) {
                JournalService::voidJournal($journal, "Reopen periode: {$reason}");
            }

            $period->update([
                'is_closed' => false,
                'closed_at' => null,
                'closed_by' => null,
            ]);

            AuditLog::create([
                'user_id' => $userId,
                'event' => 'period_reopened',
                'auditable_type' => Period::class,
                'auditable_id' => $period->id,
                'new_values' => [
                    'reason' => $reason,
                    'period_name' => $period->name,
                    'reopened_by' => User::find($userId)?->name,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);

            DB::commit();

            return $period->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public static function getNextPeriod(Period $period): ?Period
    {
        $nextMonthStart = Carbon::parse($period->start_date)->addMonth()->startOfMonth();

        return Period::where('start_date', '<=', $nextMonthStart)
            ->where('end_date', '>=', $nextMonthStart)
            ->first();
    }

    public static function createNextPeriod(Period $period): Period
    {
        $nextDate = Carbon::parse($period->start_date)->addMonth();

        $indonesianMonths = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $monthNumber = (int) $nextDate->format('n');
        $year = $nextDate->format('Y');
        $name = "{$indonesianMonths[$monthNumber]} {$year}";

        $startDate = $nextDate->copy()->startOfMonth();
        $endDate = $nextDate->copy()->endOfMonth();

        $existing = Period::where('start_date', $startDate)
            ->where('end_date', $endDate)
            ->first();

        if ($existing) {
            throw new \Exception("Periode {$name} sudah ada.");
        }

        return Period::create([
            'name' => $name,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_closed' => false,
        ]);
    }

    /**
     * Create next period without throwing if it already exists.
     * Used internally by closePeriod for auto carry forward.
     */
    private static function createNextPeriodUnsafe(Period $period): ?Period
    {
        try {
            return static::createNextPeriod($period);
        } catch (\Exception $e) {
            return static::getNextPeriod($period);
        }
    }

    public static function carryForwardBalances(Period $closedPeriod, Period $newPeriod): void
    {
        $accounts = Account::where('is_header', false)
            ->where('is_active', true)
            ->whereIn('category', ['asset', 'liability', 'equity'])
            ->get();

        DB::beginTransaction();
        try {
            $oldOpeningJournals = Journal::where('period_id', $newPeriod->id)
                ->where('type', 'opening')
                ->where('source', 'system')
                ->get();
            foreach ($oldOpeningJournals as $oldJournal) {
                if ($oldJournal->type !== 'void') {
                    JournalService::voidJournal($oldJournal, 'Perbarui saldo awal dari carry forward periode sebelumnya');
                }
            }

            OpeningBalance::where('period_id', $newPeriod->id)->delete();

            \App\Helpers\AccountResolver::clearCache();

            $journalLines = [];

            foreach ($accounts as $account) {
                $balance = JournalService::getAccountBalance($account->id, $closedPeriod->id);
                $amount = $balance['balance'];

                if (abs($amount) < 0.01) {
                    continue;
                }

                $absAmount = abs($amount);

                // Positive balance → use normal_balance position
                // Negative balance → flip position (contra-account)
                $finalPosition = $amount > 0
                    ? $account->normal_balance
                    : ($account->normal_balance === 'debit' ? 'credit' : 'debit');

                OpeningBalance::create([
                    'period_id' => $newPeriod->id,
                    'account_id' => $account->id,
                    'amount' => $absAmount,
                    'position' => $finalPosition,
                ]);

                if ($finalPosition === 'debit') {
                    $journalLines[] = [
                        'account_id' => $account->id,
                        'debit_amount' => $absAmount,
                        'credit_amount' => 0,
                    ];
                } else {
                    $journalLines[] = [
                        'account_id' => $account->id,
                        'debit_amount' => 0,
                        'credit_amount' => $absAmount,
                    ];
                }
            }

            if (count($journalLines) >= 2) {
                JournalService::createJournal([
                    'date' => $newPeriod->start_date,
                    'period_id' => $newPeriod->id,
                    'description' => "Saldo Awal — {$newPeriod->name} (carry forward dari {$closedPeriod->name})",
                    'source' => 'system',
                    'type' => 'opening',
                    'created_by' => 1,
                ], $journalLines);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
