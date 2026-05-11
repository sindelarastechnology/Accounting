<?php

namespace App\Services;

use App\Models\Journal;
use App\Models\JournalLine;
use App\Models\Period;
use App\Models\Wallet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReconciliationService
{
    /**
     * Get opening balance for a wallet up to (but not including) a date.
     */
    public static function getOpeningBalance(Wallet $wallet, string $dateFrom): float
    {
        $result = DB::table('journal_lines')
            ->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
            ->where('journals.type', '!=', 'void')
            ->where('journal_lines.wallet_id', $wallet->id)
            ->where('journals.date', '<', $dateFrom)
            ->selectRaw('COALESCE(SUM(journal_lines.debit_amount), 0) as total_debit')
            ->selectRaw('COALESCE(SUM(journal_lines.credit_amount), 0) as total_credit')
            ->first();
        return (float) $result->total_debit - (float) $result->total_credit;
    }

    /**
     * Get journal lines for a wallet within a date range.
     */
    public static function getWalletTransactions(Wallet $wallet, string $dateFrom, string $dateTo): array
    {
        return JournalLine::select('journal_lines.*')
            ->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
            ->where('journal_lines.wallet_id', $wallet->id)
            ->where('journals.type', '!=', 'void')
            ->whereBetween('journals.date', [$dateFrom, $dateTo])
            ->with(['journal', 'account'])
            ->orderBy('journals.date')
            ->orderBy('journals.id')
            ->orderBy('journal_lines.id')
            ->get()
            ->toArray();
    }

    /**
     * Get reconciliation data for a wallet in a period.
     */
    public static function getReconciliationData(Wallet $wallet, Period $period): array
    {
        $dateFrom = $period->start_date instanceof \Carbon\Carbon
            ? $period->start_date->format('Y-m-d')
            : $period->start_date;
        $dateTo = $period->end_date instanceof \Carbon\Carbon
            ? $period->end_date->format('Y-m-d')
            : $period->end_date;

        $openingBalance = self::getOpeningBalance($wallet, $dateFrom);

        $lines = self::getWalletTransactions($wallet, $dateFrom, $dateTo);

        $totalDebit = collect($lines)->sum('debit_amount');
        $totalCredit = collect($lines)->sum('credit_amount');
        $endingBalance = $openingBalance + $totalDebit - $totalCredit;

        $reconciledDebit = collect($lines)->where('reconciled_at', '!==', null)->sum('debit_amount');
        $reconciledCredit = collect($lines)->where('reconciled_at', '!==', null)->sum('credit_amount');

        // Running balance
        $runningBalance = $openingBalance;
        foreach ($lines as &$line) {
            $runningBalance += (float) $line['debit_amount'] - (float) $line['credit_amount'];
            $line['running_balance'] = $runningBalance;
        }

        return [
            'wallet' => $wallet,
            'period' => $period,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'opening_balance' => $openingBalance,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'ending_balance' => $endingBalance,
            'lines' => $lines,
            'reconciled_count' => collect($lines)->whereNotNull('reconciled_at')->count(),
            'total_count' => count($lines),
            'reconciled_debit' => $reconciledDebit,
            'reconciled_credit' => $reconciledCredit,
        ];
    }

    /**
     * Mark a journal line as reconciled.
     */
    public static function markReconciled(int $lineId, ?int $userId = null): void
    {
        $line = JournalLine::findOrFail($lineId);
        $line->update([
            'reconciled_at' => now(),
            'reconciled_by' => $userId ?? Auth::id(),
        ]);
    }

    /**
     * Unmark a journal line as reconciled.
     */
    public static function markUnreconciled(int $lineId): void
    {
        JournalLine::where('id', $lineId)->update([
            'reconciled_at' => null,
            'reconciled_by' => null,
        ]);
    }

    /**
     * Save full reconciliation: batch mark/unmark lines.
     */
    public static function saveReconciliation(
        Wallet $wallet,
        Period $period,
        array $reconciledLineIds,
        ?int $userId = null
    ): void {
        $dateFrom = $period->start_date instanceof \Carbon\Carbon
            ? $period->start_date->format('Y-m-d')
            : $period->start_date;
        $dateTo = $period->end_date instanceof \Carbon\Carbon
            ? $period->end_date->format('Y-m-d')
            : $period->end_date;

        $lineIdsInPeriod = JournalLine::where('wallet_id', $wallet->id)
            ->whereHas('journal', function ($q) use ($dateFrom, $dateTo) {
                $q->where('type', '!=', 'void')
                    ->whereBetween('date', [$dateFrom, $dateTo]);
            })
            ->pluck('id')
            ->toArray();

        $uid = $userId ?? Auth::id();
        $now = now();

        foreach ($lineIdsInPeriod as $id) {
            if (in_array($id, $reconciledLineIds)) {
                JournalLine::where('id', $id)->whereNull('reconciled_at')->update([
                    'reconciled_at' => $now,
                    'reconciled_by' => $uid,
                ]);
            } else {
                JournalLine::where('id', $id)->whereNotNull('reconciled_at')->update([
                    'reconciled_at' => null,
                    'reconciled_by' => null,
                ]);
            }
        }
    }
}
