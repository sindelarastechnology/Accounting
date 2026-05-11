<?php

namespace App\Services;

use App\Exceptions\AccountingImbalanceException;
use App\Exceptions\InvalidAccountException;
use App\Exceptions\PeriodClosedException;
use App\Helpers\AccountResolver;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\InventoryMovement;
use App\Models\Journal;
use App\Models\JournalLine;
use App\Models\OpeningBalance;
use App\Models\Period;
use App\Support\AccountingPrecision;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class JournalService
{
    public static function createJournal(array $data, array $lines): Journal
    {
        if (count($lines) < 2) {
            throw new InvalidAccountException('Jurnal minimal memiliki 2 baris.');
        }

        foreach ($lines as $index => $line) {
            $debit = (float) ($line['debit_amount'] ?? 0);
            $credit = (float) ($line['credit_amount'] ?? 0);

            if ($debit > 0 && $credit > 0) {
                throw new InvalidAccountException(
                    'Baris jurnal ke-' . ($index + 1) . ' tidak boleh memiliki debit dan kredit sekaligus.'
                );
            }

            if ($debit === 0 && $credit === 0) {
                throw new InvalidAccountException(
                    'Baris jurnal ke-' . ($index + 1) . ' harus memiliki nilai debit atau kredit.'
                );
            }

            if ($debit < 0 || $credit < 0) {
                throw new InvalidAccountException(
                    'Nilai debit/kredit tidak boleh negatif pada baris ke-' . ($index + 1) . '.'
                );
            }
        }

        $period = Period::findOrFail($data['period_id']);

        if ($period->is_closed) {
            throw new PeriodClosedException();
        }

        $journalDate = Carbon::parse($data['date']);
        if ($journalDate->lt($period->start_date) || $journalDate->gt($period->end_date)) {
            throw new \Exception(
                "Tanggal jurnal ({$data['date']}) berada di luar rentang periode " .
                "{$period->start_date->format('d/m/Y')} s/d {$period->end_date->format('d/m/Y')}."
            );
        }

        $accountIds = collect($lines)->pluck('account_id')->unique()->toArray();
        $invalidAccounts = Account::whereIn('id', $accountIds)
            ->where(function ($q) {
                $q->where('is_header', true)->orWhere('is_active', false);
            })
            ->get();

        if ($invalidAccounts->isNotEmpty()) {
            $names = $invalidAccounts->map(fn ($a) => "[{$a->code}] {$a->name}")->join(', ');
            throw new InvalidAccountException("Akun tidak valid: {$names}");
        }

        $totalDebit = collect($lines)->sum('debit_amount');
        $totalCredit = collect($lines)->sum('credit_amount');

        if (AccountingPrecision::compare($totalDebit, $totalCredit) !== 0) {
            throw new AccountingImbalanceException();
        }

        return DB::transaction(function () use ($data, $lines) {
            $journalNumber = \App\Services\DocumentNumberService::generate('journals', 'JNL', $data['date']);

            $journalData = [
                'number' => $journalNumber,
                'date' => $data['date'],
                'period_id' => $data['period_id'],
                'description' => $data['description'],
                'source' => $data['source'] ?? 'manual',
                'type' => $data['type'] ?? 'normal',
                'ref_type' => $data['ref_type'] ?? null,
                'ref_id' => $data['ref_id'] ?? null,
                'is_posted' => true,
                'created_by' => $data['created_by'] ?? Auth::id(),
            ];

            $journal = Journal::create($journalData);

            foreach ($lines as $line) {
                JournalLine::create([
                    'journal_id' => $journal->id,
                    'account_id' => $line['account_id'],
                    'debit_amount' => $line['debit_amount'] ?? 0,
                    'credit_amount' => $line['credit_amount'] ?? 0,
                    'wallet_id' => $line['wallet_id'] ?? null,
                    'description' => $line['description'] ?? null,
                ]);
            }

            // Create audit log after lines are created
            AuditLog::create([
                'user_id' => $data['created_by'] ?? Auth::id(),
                'event' => 'created',
                'auditable_type' => Journal::class,
                'auditable_id' => $journal->id,
                'old_values' => null,
                'new_values' => [
                    'number' => $journal->number,
                    'total_debit' => $journal->lines->sum('debit_amount'),
                    'total_credit' => $journal->lines->sum('credit_amount'),
                    'description' => $journal->description,
                ],
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'created_at' => now(),
            ]);

            return $journal->load('lines.account');
        });
    }

    public static function voidJournal(Journal $journal, string $reason): Journal
    {
        if ($journal->type === 'void' || $journal->type === 'reversal') {
            throw new InvalidAccountException('Jurnal ini tidak dapat di-void.');
        }

        $activePeriod = Period::active()
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();

        if (!$activePeriod) {
            throw new PeriodClosedException('Tidak ada periode aktif untuk membuat jurnal reversal.');
        }

        return DB::transaction(function () use ($journal, $reason, $activePeriod) {
            $reversalLines = $journal->lines->map(function ($line) {
                return [
                    'account_id' => $line->account_id,
                    'debit_amount' => $line->credit_amount,
                    'credit_amount' => $line->debit_amount,
                    'wallet_id' => $line->wallet_id,
                    'description' => $line->description,
                ];
            })->toArray();

            $reversalData = [
                'date' => now(),
                'period_id' => $activePeriod->id,
                'description' => $reason,
                'source' => 'manual',
                'type' => 'reversal',
                'ref_type' => 'journals',
                'ref_id' => $journal->id,
                'created_by' => Auth::id(),
            ];

            $reversalJournal = self::createJournal($reversalData, $reversalLines);

            $journal->update([
                'type' => 'void',
                'reversed_by_journal_id' => $reversalJournal->id,
            ]);

            $relatedMovements = \App\Models\InventoryMovement::where('journal_id', $journal->id)->get();
            foreach ($relatedMovements as $movement) {
                $reverseQty = -(float) $movement->qty;
                \App\Models\InventoryMovement::create([
                    'product_id' => $movement->product_id,
                    'date' => now(),
                    'type' => $movement->type === 'in' ? 'out' : 'in',
                    'ref_type' => 'journal_void',
                    'ref_id' => $reversalJournal->id,
                    'qty' => $reverseQty,
                    'unit_cost' => (float) $movement->unit_cost,
                    'total_cost' => (float) $movement->total_cost,
                    'description' => "Reversal: {$movement->description}",
                    'journal_id' => $reversalJournal->id,
                    'created_by' => Auth::id(),
                ]);
            }

            return $reversalJournal;
        });
    }

    public static function getAccountBalance(int $accountId, ?int $periodId = null): array
    {
        $account = Account::findOrFail($accountId);

        $journalQuery = DB::table('journal_lines')
            ->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
            ->where('journal_lines.account_id', $accountId)
            ->where('journals.type', '!=', 'void');

        if ($periodId) {
            $journalQuery->where('journals.period_id', $periodId);
        }

        $totals = $journalQuery->selectRaw('
            COALESCE(SUM(journal_lines.debit_amount), 0) as total_debit,
            COALESCE(SUM(journal_lines.credit_amount), 0) as total_credit
        ')->first();

        $totalDebit = (float) $totals->total_debit;
        $totalCredit = (float) $totals->total_credit;

        if ($account->normal_balance === 'debit') {
            $balance = $totalDebit - $totalCredit;
        } else {
            $balance = $totalCredit - $totalDebit;
        }

        return [
            'debit' => $totalDebit,
            'credit' => $totalCredit,
            'balance' => $balance,
            'normal_balance' => $account->normal_balance,
        ];
    }

    public static function getTrialBalance(?int $periodId = null): Collection
    {
        return Account::where('is_header', false)
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->map(function (Account $account) use ($periodId) {
                $balance = self::getAccountBalance($account->id, $periodId);

                return [
                    'account' => $account,
                    'debit' => $balance['debit'],
                    'credit' => $balance['credit'],
                    'balance' => $balance['balance'],
                    'normal_balance' => $balance['normal_balance'],
                ];
            });
    }

    public static function getAccountBalanceUpTo(?int $accountId, ?string $asOfDate = null, ?int $periodId = null): array
    {
        $account = Account::findOrFail($accountId);

        $journalQuery = DB::table('journal_lines')
            ->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
            ->where('journal_lines.account_id', $accountId)
            ->where('journals.type', '!=', 'void');

        if ($periodId) {
            $journalQuery->where('journals.period_id', $periodId);
        } elseif ($asOfDate) {
            $journalQuery->where('journals.date', '<=', $asOfDate);
        }

        $totals = $journalQuery->selectRaw('
            COALESCE(SUM(journal_lines.debit_amount), 0) as total_debit,
            COALESCE(SUM(journal_lines.credit_amount), 0) as total_credit
        ')->first();

        $totalDebit = (float) $totals->total_debit;
        $totalCredit = (float) $totals->total_credit;

        if ($account->normal_balance === 'debit') {
            $balance = $totalDebit - $totalCredit;
        } else {
            $balance = $totalCredit - $totalDebit;
        }

        return [
            'debit' => $totalDebit,
            'credit' => $totalCredit,
            'balance' => $balance,
            'normal_balance' => $account->normal_balance,
        ];
    }

    public static function getAccountBalanceUpToDate(?int $accountId, string $asOfDate): array
    {
        $account = Account::findOrFail($accountId);

        $totals = DB::table('journal_lines')
            ->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
            ->where('journal_lines.account_id', $accountId)
            ->where('journals.type', '!=', 'void')
            ->where('journals.date', '<=', $asOfDate)
            ->selectRaw('
                COALESCE(SUM(journal_lines.debit_amount), 0) as total_debit,
                COALESCE(SUM(journal_lines.credit_amount), 0) as total_credit
            ')->first();

        $totalDebit = (float) $totals->total_debit;
        $totalCredit = (float) $totals->total_credit;

        if ($account->normal_balance === 'debit') {
            $balance = $totalDebit - $totalCredit;
        } else {
            $balance = $totalCredit - $totalDebit;
        }

        return [
            'debit' => $totalDebit,
            'credit' => $totalCredit,
            'balance' => $balance,
            'normal_balance' => $account->normal_balance,
        ];
    }

    public static function getAccountBalanceChange(int $accountId, string $dateFrom, string $dateTo): float
    {
        $balanceEnd = self::getAccountBalanceUpToDate($accountId, $dateTo);
        $dayBeforeStart = Carbon::parse($dateFrom)->subDay()->format('Y-m-d');
        $balanceStart = self::getAccountBalanceUpToDate($accountId, $dayBeforeStart);
        return $balanceEnd['balance'] - $balanceStart['balance'];
    }

    public static function getIncomeStatement(?int $period_id = null, ?string $date_from = null, ?string $date_to = null): array
    {
        $accounts = Account::where('is_header', false)
            ->where('is_active', true)
            ->whereIn('category', ['revenue', 'cogs', 'expense'])
            ->orderBy('code')
            ->get();

        $journalQuery = DB::table('journal_lines')
            ->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
            ->where('journals.type', '!=', 'void');

        if ($period_id) {
            $journalQuery->where('journals.period_id', $period_id);
        } elseif ($date_from && $date_to) {
            $journalQuery->whereBetween('journals.date', [$date_from, $date_to]);
        } elseif ($date_to) {
            $journalQuery->where('journals.date', '<=', $date_to);
        }

        $balances = $journalQuery
            ->join('accounts', 'journal_lines.account_id', '=', 'accounts.id')
            ->whereIn('accounts.category', ['revenue', 'cogs', 'expense'])
            ->selectRaw('
                accounts.id as account_id,
                COALESCE(SUM(journal_lines.debit_amount), 0) as total_debit,
                COALESCE(SUM(journal_lines.credit_amount), 0) as total_credit
            ')
            ->groupBy('accounts.id')
            ->get()
            ->keyBy('account_id');

        $revenueOperating = [];
        $revenueOther = [];
        $cogs = [];
        $expenseOperating = [];
        $expenseOther = [];

        foreach ($accounts as $account) {
            $balanceData = $balances->get($account->id);
            $debit = $balanceData ? (float) $balanceData->total_debit : 0;
            $credit = $balanceData ? (float) $balanceData->total_credit : 0;

            if ($account->normal_balance === 'debit') {
                $balance = $debit - $credit;
            } else {
                $balance = $credit - $debit;
            }

            $item = ['account' => $account, 'balance' => $balance];

            if ($account->category === 'revenue') {
                if (str_starts_with($account->code, '4200') || str_starts_with($account->code, '42') || str_starts_with($account->code, '43')) {
                    $revenueOther[] = $item;
                } else {
                    $revenueOperating[] = $item;
                }
            } elseif ($account->category === 'cogs') {
                $cogs[] = $item;
            } elseif ($account->category === 'expense') {
                if (str_starts_with($account->code, '6600') || str_starts_with($account->code, '66')) {
                    $expenseOther[] = $item;
                } else {
                    $expenseOperating[] = $item;
                }
            }
        }

        $totalRevenueOperating = collect($revenueOperating)->sum('balance');
        $totalRevenueOther = collect($revenueOther)->sum('balance');
        $totalCogs = collect($cogs)->sum('balance');
        $totalExpenseOperating = collect($expenseOperating)->sum('balance');
        $totalExpenseOther = collect($expenseOther)->sum('balance');

        $totalRevenue = $totalRevenueOperating + $totalRevenueOther;
        $totalExpenses = $totalExpenseOperating + $totalExpenseOther;
        $grossProfit = $totalRevenue - $totalCogs;
        $operatingProfit = $grossProfit - $totalExpenseOperating;
        $otherTotal = $totalRevenueOther - $totalExpenseOther;
        $incomeBeforeTax = $totalRevenue - $totalCogs - $totalExpenses;

        // Detect tax expense accounts (kode 8xxx with pajak/pph in name)
        $taxExpense = 0;
        $taxExpenseAccounts = [];
        foreach ($expenseOther as $item) {
            $name = strtolower($item['account']->name);
            if (str_contains($name, 'pajak') || str_contains($name, 'pph') || str_contains($name, 'tax')) {
                $taxExpense += $item['balance'];
                $taxExpenseAccounts[] = $item;
            }
        }

        $netIncome = $incomeBeforeTax - $taxExpense;

        // Legacy keys (all revenue + all expense for backward compat)
        $revenues = array_merge($revenueOperating, $revenueOther);
        $expenses = array_merge($expenseOperating, $expenseOther);

        if ($period_id) {
            $period = Period::find($period_id);
            $periodLabel = $period ? $period->name : 'Periode Tidak Dikenal';
        } elseif ($date_from && $date_to) {
            $periodLabel = Carbon::parse($date_from)->format('d/m/Y') . ' s/d ' . Carbon::parse($date_to)->format('d/m/Y');
        } else {
            $periodLabel = 'Custom Range';
        }

        // ===== HPP BREAKDOWN (FIFO-based analytical) =====
        $hppDateFrom = $date_from;
        $hppDateTo = $date_to;

        if ($period_id) {
            $hppPeriod = Period::find($period_id);
            if ($hppPeriod) {
                $hppDateFrom = $hppPeriod->start_date instanceof Carbon
                    ? $hppPeriod->start_date->format('Y-m-d')
                    : $hppPeriod->start_date;
                $hppDateTo = $hppPeriod->end_date instanceof Carbon
                    ? $hppPeriod->end_date->format('Y-m-d')
                    : $hppPeriod->end_date;
            }
        }

        $hppDateTo = $hppDateTo ?? now()->format('Y-m-d');

        $inventoryAccountId = AccountResolver::inventory();
        $openingInventory = 0;
        $prevPeriod = null;

        if ($period_id) {
            $hppPeriod = $hppPeriod ?? Period::find($period_id);
            if ($hppPeriod) {
                $prevPeriod = Period::where('end_date', '<', $hppPeriod->start_date)
                    ->orderBy('end_date', 'desc')
                    ->first();
            }
        }

        if ($prevPeriod) {
            $bal = self::getAccountBalance($inventoryAccountId, $prevPeriod->id);
            $openingInventory = $bal['balance'];
        } elseif ($hppDateFrom) {
            $dayBefore = Carbon::parse($hppDateFrom)->subDay()->format('Y-m-d');
            $bal = self::getAccountBalanceUpToDate($inventoryAccountId, $dayBefore);
            $openingInventory = $bal['balance'];
        }

        $netPurchases = (float) InventoryMovement::where('type', 'in')
            ->whereBetween('date', [$hppDateFrom, $hppDateTo])
            ->sum('total_cost');

        $purchaseReturns = (float) InventoryMovement::where('type', 'out')
            ->where('ref_type', 'debit_notes')
            ->whereBetween('date', [$hppDateFrom, $hppDateTo])
            ->sum(DB::raw('ABS(total_cost)'));

        $goodsAvailable = $openingInventory + $netPurchases - $purchaseReturns;
        $closingInventory = FifoCostService::getClosingInventoryValue($hppDateTo);
        $hppCalculated = max(0, $goodsAvailable - $closingInventory);
        $hppDifference = $totalCogs - $hppCalculated;

        return [
            // Legacy keys
            'revenues' => $revenues,
            'cogs' => $cogs,
            'expenses' => $expenses,
            'total_revenue' => $totalRevenue,
            'total_cogs' => $totalCogs,
            'gross_profit' => $grossProfit,
            'total_expenses' => $totalExpenses,
            'net_income' => $netIncome,
            'period_label' => $periodLabel,

            // New structured keys
            'revenue_operating' => $revenueOperating,
            'revenue_other' => $revenueOther,
            'total_revenue_operating' => $totalRevenueOperating,
            'total_revenue_other' => $totalRevenueOther,
            'expense_operating' => $expenseOperating,
            'expense_other' => $expenseOther,
            'total_expense_operating' => $totalExpenseOperating,
            'total_expense_other' => $totalExpenseOther,
            'operating_profit' => $operatingProfit,
            'other_total' => $otherTotal,
            'income_before_tax' => $incomeBeforeTax,
            'tax_expense' => $taxExpense,
            'tax_expense_accounts' => $taxExpenseAccounts,

            // HPP Breakdown
            'hpp_breakdown' => [
                'opening_inventory' => $openingInventory,
                'net_purchases' => $netPurchases,
                'purchase_returns' => $purchaseReturns,
                'goods_available' => $goodsAvailable,
                'closing_inventory' => $closingInventory,
                'hpp_calculated' => $hppCalculated,
                'hpp_from_journal' => $totalCogs,
                'hpp_difference' => $hppDifference,
            ],
        ];
    }

    public static function getBalanceSheet(?int $period_id = null, ?string $as_of_date = null): array
    {
        if (!$as_of_date && $period_id) {
            $period = Period::find($period_id);
            if ($period) {
                $as_of_date = $period->end_date instanceof Carbon ? $period->end_date->format('Y-m-d') : $period->end_date;
            }
        }

        if (!$as_of_date) {
            $as_of_date = now()->format('Y-m-d');
        }

        $journalQuery = DB::table('journal_lines')
            ->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
            ->where('journals.type', '!=', 'void')
            ->where('journals.date', '<=', $as_of_date);

        $balances = $journalQuery
            ->join('accounts', 'journal_lines.account_id', '=', 'accounts.id')
            ->whereIn('accounts.category', ['asset', 'liability', 'equity'])
            ->where('accounts.is_header', false)
            ->selectRaw('
                accounts.id as account_id,
                accounts.category,
                accounts.parent_id,
                COALESCE(SUM(journal_lines.debit_amount), 0) as total_debit,
                COALESCE(SUM(journal_lines.credit_amount), 0) as total_credit
            ')
            ->groupBy('accounts.id', 'accounts.category', 'accounts.parent_id')
            ->get()
            ->keyBy('account_id');

        $allAccounts = Account::where('is_header', false)
            ->where('is_active', true)
            ->whereIn('category', ['asset', 'liability', 'equity'])
            ->orderBy('code')
            ->get();

        $assetCurrent = [];
        $assetFixed = [];
        $liabilityCurrent = [];
        $liabilityLong = [];
        $equityAccounts = [];

        $parentMap = [];
        $parents = Account::where('is_header', true)->get();
        foreach ($parents as $p) {
            $parentMap[$p->id] = $p;
        }

        foreach ($allAccounts as $account) {
            $balanceData = $balances->get($account->id);

            $totalDebit = $balanceData ? (float) $balanceData->total_debit : 0;
            $totalCredit = $balanceData ? (float) $balanceData->total_credit : 0;

            if ($account->normal_balance === 'debit') {
                $balance = $totalDebit - $totalCredit;
            } else {
                $balance = $totalCredit - $totalDebit;
            }

            $item = ['account' => $account, 'balance' => $balance];

            if ($account->category === 'asset') {
                $parentId = $account->parent_id;
                $parentCode = $parentId && isset($parentMap[$parentId]) ? $parentMap[$parentId]->code : null;

                if ($parentCode && (str_starts_with($parentCode, '1700') || str_starts_with($parentCode, '17') || str_starts_with($parentCode, '1800') || str_starts_with($parentCode, '18'))) {
                    $assetFixed[] = $item;
                } else {
                    $assetCurrent[] = $item;
                }
            } elseif ($account->category === 'liability') {
                $parentId = $account->parent_id;
                $parentCode = $parentId && isset($parentMap[$parentId]) ? $parentMap[$parentId]->code : null;

                if ($parentCode && (str_starts_with($parentCode, '2300') || str_starts_with($parentCode, '23'))) {
                    $liabilityLong[] = $item;
                } else {
                    $liabilityCurrent[] = $item;
                }
            } elseif ($account->category === 'equity') {
                $equityAccounts[] = $item;
            }
        }

        $totalAssetCurrent = collect($assetCurrent)->sum('balance');
        $totalAssetFixed = collect($assetFixed)->sum('balance');
        $totalAssets = $totalAssetCurrent + $totalAssetFixed;

        $totalLiabilityCurrent = collect($liabilityCurrent)->sum('balance');
        $totalLiabilityLong = collect($liabilityLong)->sum('balance');
        $totalLiabilities = $totalLiabilityCurrent + $totalLiabilityLong;

        $incomeStatement = self::getIncomeStatement(null, null, $as_of_date);
        $netIncome = $incomeStatement['net_income'];

        $totalEquityAccounts = collect($equityAccounts)->sum('balance');
        $totalEquity = $totalEquityAccounts + $netIncome;

        $difference = $totalAssets - ($totalLiabilities + $totalEquity);
        $isBalanced = abs($difference) < 0.01;

        return [
            'assets' => [
                'current' => $assetCurrent,
                'fixed' => $assetFixed,
                'total_current' => $totalAssetCurrent,
                'total_fixed' => $totalAssetFixed,
                'total' => $totalAssets,
            ],
            'liabilities' => [
                'current' => $liabilityCurrent,
                'long' => $liabilityLong,
                'total_current' => $totalLiabilityCurrent,
                'total_long' => $totalLiabilityLong,
                'total' => $totalLiabilities,
            ],
            'equity' => [
                'accounts' => $equityAccounts,
                'net_income' => $netIncome,
                'total' => $totalEquity,
            ],
            'is_balanced' => $isBalanced,
            'difference' => $difference,
            'as_of_date' => $as_of_date,
        ];
    }

    public static function getCashFlow(?int $period_id = null, ?string $date_from = null, ?string $date_to = null): array
    {
        if ($period_id && !$date_from && !$date_to) {
            $period = Period::find($period_id);
            if ($period) {
                $date_from = $period->start_date instanceof Carbon ? $period->start_date->format('Y-m-d') : $period->start_date;
                $date_to = $period->end_date instanceof Carbon ? $period->end_date->format('Y-m-d') : $period->end_date;
            }
        }

        if (!$date_from) {
            $date_from = now()->startOfMonth()->format('Y-m-d');
        }
        if (!$date_to) {
            $date_to = now()->format('Y-m-d');
        }

        $incomeStatement = self::getIncomeStatement($period_id, $date_from, $date_to);
        $netIncome = $incomeStatement['net_income'];

        // ===== AKTIVITAS OPERASI (Metode Tidak Langsung) =====
        $arAccountId = AccountResolver::receivable();
        $inventoryAccountId = AccountResolver::inventory();
        $apAccountId = AccountResolver::payable();

        $arChange = self::getAccountBalanceChange($arAccountId, $date_from, $date_to);
        $invChange = self::getAccountBalanceChange($inventoryAccountId, $date_from, $date_to);
        $apChange = self::getAccountBalanceChange($apAccountId, $date_from, $date_to);

        $operatingAdjustments = [];

        $arLabel = $arChange > 0 ? 'Kenaikan Piutang Usaha' : ($arChange < 0 ? 'Penurunan Piutang Usaha' : 'Kenaikan Piutang Usaha');
        $operatingAdjustments[] = ['label' => $arLabel, 'amount' => -$arChange];

        $invLabel = $invChange > 0 ? 'Kenaikan Persediaan' : ($invChange < 0 ? 'Penurunan Persediaan' : 'Penurunan Persediaan');
        $operatingAdjustments[] = ['label' => $invLabel, 'amount' => -$invChange];

        $apLabel = $apChange > 0 ? 'Kenaikan Hutang Usaha' : ($apChange < 0 ? 'Penurunan Hutang Usaha' : 'Kenaikan Hutang Usaha');
        $operatingAdjustments[] = ['label' => $apLabel, 'amount' => $apChange];

        // Penyesuaian non-tunai: tambahkan kembali beban penyusutan
        $depreciationAmount = DB::table('journal_lines')
            ->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
            ->join('accounts', 'journal_lines.account_id', '=', 'accounts.id')
            ->where('journals.type', '!=', 'void')
            ->where('journals.date', '>=', $date_from)
            ->where('journals.date', '<=', $date_to)
            ->where('accounts.category', 'expense')
            ->where(function ($q) {
                $q->where('accounts.code', 'like', '66%')
                  ->orWhere('accounts.name', 'like', '%penyusutan%')
                  ->orWhere('accounts.name', 'like', '%amortisasi%')
                  ->orWhere('accounts.name', 'like', '%depreciation%');
            })
            ->sum('journal_lines.debit_amount');

        if ((float) $depreciationAmount > 0) {
            $operatingAdjustments[] = [
                'label' => 'Beban Penyusutan',
                'amount' => (float) $depreciationAmount,
            ];
        }

        $totalOperating = $netIncome + collect($operatingAdjustments)->sum('amount');

        // ===== AKTIVITAS INVESTASI =====
        $fixedAssetAccounts = Account::where('is_header', false)
            ->where('is_active', true)
            ->where('category', 'asset')
            ->whereHas('parent', fn ($q) => $q->where('code', 'like', '15%'))
            ->get();

        $investingItems = [];
        foreach ($fixedAssetAccounts as $fa) {
            $change = self::getAccountBalanceChange($fa->id, $date_from, $date_to);
            if (abs($change) > 0.01) {
                $investingItems[] = [
                    'label' => $fa->name,
                    'amount' => -$change,
                ];
            }
        }

        $totalInvesting = collect($investingItems)->sum('amount');

        // ===== AKTIVITAS PENDANAAN =====
        $financingItems = [];

        // Helper: resolve label suffix dari source jurnal untuk ekuitas
        $equityLabelSuffix = function (string $source, string $journalDescription, string $accountName): string {
            return match ($source) {
                'opening' => $accountName . ' — Saldo Awal',
                'other_receipt' => $accountName . ' — Tambahan Modal',
                default => $journalDescription ?: $accountName,
            };
        };

        // Helper: ambil wallet name dari debit line pada jurnal yang sama
        $walletNameFromJournal = function (int $journalId) {
            return DB::table('journal_lines')
                ->join('wallets', 'journal_lines.wallet_id', '=', 'wallets.id')
                ->where('journal_lines.journal_id', $journalId)
                ->where('journal_lines.debit_amount', '>', 0)
                ->whereNotNull('journal_lines.wallet_id')
                ->value('wallets.name');
        };

        // 1. Mutasi equity accounts per transaksi (bukan per akun)
        //    Exclude: 3200 Laba Ditahan, 3300 Ikhtisar L/R
        $equityAccountIds = Account::where('is_header', false)
            ->where('is_active', true)
            ->where('category', 'equity')
            ->whereNot('code', 'like', '3200-00-%')
            ->pluck('id');

        if ($equityAccountIds->isNotEmpty()) {
            $equityTransactions = DB::table('journal_lines')
                ->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
                ->join('accounts', 'journal_lines.account_id', '=', 'accounts.id')
                ->whereIn('journal_lines.account_id', $equityAccountIds)
                ->where('journals.type', '!=', 'void')
                ->where('journals.date', '>=', $date_from)
                ->where('journals.date', '<=', $date_to)
                ->selectRaw('
                    journals.id as journal_id,
                    journals.description as journal_description,
                    journals.source,
                    accounts.id as account_id,
                    accounts.name as account_name,
                    accounts.code,
                    accounts.normal_balance,
                    COALESCE(SUM(journal_lines.debit_amount), 0) as total_debit,
                    COALESCE(SUM(journal_lines.credit_amount), 0) as total_credit
                ')
                ->groupBy(
                    'journals.id', 'journals.description', 'journals.source',
                    'accounts.id', 'accounts.name', 'accounts.code', 'accounts.normal_balance'
                )
                ->get();

            foreach ($equityTransactions as $txn) {
                $netMovement = (float) $txn->total_credit - (float) $txn->total_debit;

                if (abs($netMovement) > 0.01) {
                    $label = $equityLabelSuffix($txn->source, $txn->journal_description, $txn->account_name);

                    // Untuk opening balance, tambahkan nama wallet
                    if ($txn->source === 'opening') {
                        $walletName = $walletNameFromJournal($txn->journal_id);
                        if ($walletName) {
                            $label .= ' ' . $walletName;
                        }
                    }

                    $financingItems[] = [
                        'label' => $label,
                        'amount' => $netMovement,
                    ];
                }
            }
        }

        // 2. Hutang bank jangka panjang / pinjaman (code 25xx+)
        $ltAccountIds = Account::where('is_header', false)
            ->where('is_active', true)
            ->where('category', 'liability')
            ->where('code', 'like', '2300%')
            ->pluck('id');

        if ($ltAccountIds->isNotEmpty()) {
            $ltMutations = DB::table('journal_lines')
                ->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
                ->join('accounts', 'journal_lines.account_id', '=', 'accounts.id')
                ->whereIn('journal_lines.account_id', $ltAccountIds)
                ->where('journals.type', '!=', 'void')
                ->where('journals.date', '>=', $date_from)
                ->where('journals.date', '<=', $date_to)
                ->selectRaw('
                    accounts.id,
                    accounts.name as account_name,
                    accounts.code,
                    accounts.normal_balance,
                    COALESCE(SUM(journal_lines.debit_amount), 0) as total_debit,
                    COALESCE(SUM(journal_lines.credit_amount), 0) as total_credit
                ')
                ->groupBy('accounts.id', 'accounts.name', 'accounts.code', 'accounts.normal_balance')
                ->get();

            foreach ($ltMutations as $mut) {
                $netMovement = (float) $mut->total_credit - (float) $mut->total_debit;
                if (abs($netMovement) > 0.01) {
                    $financingItems[] = [
                        'label' => $mut->account_name,
                        'amount' => $netMovement,
                    ];
                }
            }
        }

        $totalFinancing = collect($financingItems)->sum('amount');

        // ===== KENAIKAN KAS BERSIH =====
        $netChange = $totalOperating + $totalInvesting + $totalFinancing;

        // ===== SALDO KAS AWAL = transaksi SEBELUM periode (eksklusif) =====
        // Dihitung dari data jurnal: SUM(debit - credit) semua akun kas/bank
        // sebelum tanggal awal periode. Untuk periode pertama hasilnya Rp 0.
        $walletAccounts = \App\Models\Wallet::active()->pluck('account_id')->toArray();
        $openingCash = 0;
        $dayBeforeStart = Carbon::parse($date_from)->subDay()->format('Y-m-d');
        foreach ($walletAccounts as $waId) {
            $bal = self::getAccountBalanceUpToDate($waId, $dayBeforeStart);
            $openingCash += $bal['balance'];
        }

        $closingCash = $openingCash + $netChange;

        // ===== VALIDASI SILANG =====
        // Hitung saldo kas aktual dari semua akun kas/bank per tanggal akhir periode
        $actualClosingCash = 0;
        foreach ($walletAccounts as $waId) {
            $bal = self::getAccountBalanceUpToDate($waId, $date_to);
            $actualClosingCash += $bal['balance'];
        }

        $unclassifiedAmount = 0;
        if (abs($closingCash - $actualClosingCash) > 0.01) {
            $unclassifiedAmount = $actualClosingCash - $closingCash;
            Log::warning('Laporan Arus Kas: selisih tidak terklasifikasi', [
                'opening_cash' => $openingCash,
                'closing_cash' => $closingCash,
                'actual_closing_cash' => $actualClosingCash,
                'difference' => $unclassifiedAmount,
                'date_from' => $date_from,
                'date_to' => $date_to,
                'period_id' => $period_id,
            ]);
        }

        Log::info('Laporan Arus Kas: verifikasi silang', [
            'opening_cash' => $openingCash,
            'net_change' => $netChange,
            'closing_cash' => $closingCash,
            'actual_closing_cash' => $actualClosingCash,
            'match' => abs($closingCash - $actualClosingCash) < 0.01 ? 'OK' : 'MISMATCH',
        ]);

        return [
            'operating' => [
                'net_income' => $netIncome,
                'adjustments' => $operatingAdjustments,
                'total' => $totalOperating,
            ],
            'investing' => [
                'items' => $investingItems,
                'total' => $totalInvesting,
            ],
            'financing' => [
                'items' => $financingItems,
                'total' => $totalFinancing,
            ],
            'net_change' => $netChange,
            'opening_cash' => $openingCash,
            'closing_cash' => $closingCash,
            'unclassified_amount' => $unclassifiedAmount,
            'date_from' => $date_from,
            'date_to' => $date_to,
        ];
    }

    public static function getCashInOut(string $date_from, string $date_to): array
    {
        $walletAccountIds = \App\Models\Wallet::active()->pluck('account_id')->filter()->values()->toArray();

        if (empty($walletAccountIds)) {
            return [
                'cash_in' => [['label' => 'Tidak ada data', 'amount' => 0], 'total' => 0],
                'cash_out' => [['label' => 'Tidak ada data', 'amount' => 0], 'total' => 0],
                'net' => 0,
            ];
        }

        $rows = DB::table('journal_lines')
            ->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
            ->leftJoin('payments', function ($join) {
                $join->on('journals.ref_id', '=', 'payments.id')
                    ->where('journals.ref_type', '=', 'payments');
            })
            ->whereIn('journal_lines.account_id', $walletAccountIds)
            ->where('journals.type', '!=', 'void')
            ->where('journals.source', '!=', 'transfer')
            ->where('journals.date', '>=', $date_from)
            ->where('journals.date', '<=', $date_to)
            ->selectRaw('
                journal_lines.debit_amount,
                journal_lines.credit_amount,
                journals.source,
                journals.ref_type,
                journals.ref_id,
                payments.payable_type,
                journals.date
            ')
            ->orderBy('journals.date')
            ->get();

        $cashInGroups = [];
        $cashOutGroups = [];

        // Cache for other_receipt types
        $receiptTypeCache = [];

        foreach ($rows as $row) {
            $source = $row->source;
            $payableType = $row->payable_type;

            // Resolve other_receipt type label
            if ($source === 'other_receipt' && $row->ref_type === 'other_receipts' && $row->ref_id) {
                if (!isset($receiptTypeCache[$row->ref_id])) {
                    $receiptTypeCache[$row->ref_id] = \App\Models\OtherReceipt::where('id', $row->ref_id)->value('receipt_type');
                }
            }
            $receiptType = $receiptTypeCache[$row->ref_id] ?? null;

            if ((float) $row->debit_amount > 0) {
                // Kas Masuk
                $label = match (true) {
                    $source === 'payment' && $payableType === 'invoices' => 'Penerimaan dari Customer',
                    $source === 'opening' => 'Saldo Awal / Setoran Modal',
                    $source === 'other_receipt' && $receiptType === 'capital_injection' => 'Tambahan Modal',
                    $source === 'other_receipt' && $receiptType === 'owner_loan' => 'Pinjaman dari Pemilik',
                    $source === 'other_receipt' && $receiptType === 'other_income' => 'Pendapatan Lain-lain',
                    $source === 'other_receipt' && $receiptType === 'refund' => 'Pengembalian Dana',
                    $source === 'other_receipt' => 'Penerimaan Lainnya',
                    $source === 'manual' => 'Penerimaan Lain-lain',
                    default => \App\Models\Journal::sources()[$source] ?? ucfirst($source),
                };
                $cashInGroups[$label] = ($cashInGroups[$label] ?? 0) + (float) $row->debit_amount;
            }

            if ((float) $row->credit_amount > 0) {
                // Kas Keluar
                $label = match (true) {
                    $source === 'payment' && $payableType === 'purchases' => 'Pembayaran ke Supplier',
                    $source === 'purchase' => 'Pembelian Tunai',
                    $source === 'expense' => 'Beban Operasional',
                    $source === 'manual' => 'Pengeluaran Lain-lain',
                    default => \App\Models\Journal::sources()[$source] ?? ucfirst($source),
                };
                $cashOutGroups[$label] = ($cashOutGroups[$label] ?? 0) + (float) $row->credit_amount;
            }
        }

        $cashInItems = [];
        foreach ($cashInGroups as $label => $amount) {
            $cashInItems[] = ['label' => $label, 'amount' => round($amount, 2)];
        }
        $cashOutItems = [];
        foreach ($cashOutGroups as $label => $amount) {
            $cashOutItems[] = ['label' => $label, 'amount' => round($amount, 2)];
        }

        $totalIn = round(collect($cashInItems)->sum('amount'), 2);
        $totalOut = round(collect($cashOutItems)->sum('amount'), 2);

        return [
            'cash_in' => $cashInItems,
            'cash_in_total' => $totalIn,
            'cash_out' => $cashOutItems,
            'cash_out_total' => $totalOut,
            'net' => round($totalIn - $totalOut, 2),
        ];
    }

    public static function getPpnReport(?int $period_id = null, ?string $date_from = null, ?string $date_to = null): array
    {
        $taxQuery = function ($type) {
            return \App\Models\TaxRule::where('type', $type)
                ->where('is_active', true)
                ->get();
        };

        $ppnMasukanRules = $taxQuery('ppn')->filter(fn ($rule) =>
            in_array($rule->module, ['purchase', 'both'])
        );

        $ppnKeluaranRules = $taxQuery('ppn')->filter(fn ($rule) =>
            in_array($rule->module, ['sales', 'both'])
        );

        $dateCondition = function ($query) use ($period_id, $date_from, $date_to) {
            if ($period_id) {
                $period = \App\Models\Period::find($period_id);
                if ($period) {
                    return $query->whereBetween('date', [$period->start_date, $period->end_date]);
                }
            } elseif ($date_from && $date_to) {
                return $query->whereBetween('date', [$date_from, $date_to]);
            }
            return $query;
        };

        $ppnMasukan = [];
        $ppnKeluaran = [];

        $invoices = $dateCondition(\App\Models\Invoice::whereIn('status', ['posted', 'partially_paid', 'paid']))->get();
        foreach ($invoices as $invoice) {
            foreach ($invoice->taxes as $invoiceTax) {
                if ($ppnKeluaranRules->contains('id', $invoiceTax->tax_rule_id)) {
                    $baseAmount = (float) $invoice->subtotal - (float) $invoice->discount_amount;
                    $ppnKeluaran[] = [
                        'date' => $invoice->date,
                        'number' => $invoice->number,
                        'contact' => $invoice->contact->name ?? '-',
                        'description' => 'Invoice',
                        'base_amount' => $baseAmount,
                        'tax_amount' => (float) $invoiceTax->tax_amount,
                        'type' => 'invoice',
                    ];
                }
            }
        }

        $purchases = $dateCondition(\App\Models\Purchase::whereIn('status', ['posted', 'partially_paid', 'paid']))->get();
        foreach ($purchases as $purchase) {
            foreach ($purchase->taxes as $purchaseTax) {
                if ($ppnMasukanRules->contains('id', $purchaseTax->tax_rule_id)) {
                    $baseAmount = (float) $purchase->subtotal - (float) $purchase->discount_amount;
                    $ppnMasukan[] = [
                        'date' => $purchase->date,
                        'number' => $purchase->number,
                        'contact' => $purchase->contact->name ?? '-',
                        'description' => 'Purchase',
                        'base_amount' => $baseAmount,
                        'tax_amount' => (float) $purchaseTax->tax_amount,
                        'type' => 'purchase',
                    ];
                }
            }
        }

        $creditNotes = $dateCondition(\App\Models\CreditNote::where('status', 'posted'))->get();
        foreach ($creditNotes as $cn) {
            if ((float) $cn->tax_amount > 0) {
                $ppnKeluaran[] = [
                    'date' => $cn->date,
                    'number' => $cn->number,
                    'contact' => $cn->contact->name ?? '-',
                    'description' => 'Credit Note (Retur)',
                    'base_amount' => (float) ($cn->subtotal - ($cn->discount_amount ?? 0)),
                    'tax_amount' => -(float) $cn->tax_amount,
                    'type' => 'credit_note',
                ];
            }
        }

        $debitNotes = $dateCondition(\App\Models\DebitNote::where('status', 'posted'))->get();
        foreach ($debitNotes as $dn) {
            if ((float) $dn->tax_amount > 0) {
                $ppnMasukan[] = [
                    'date' => $dn->date,
                    'number' => $dn->number,
                    'contact' => $dn->contact->name ?? '-',
                    'description' => 'Debit Note (Retur)',
                    'base_amount' => (float) ($dn->subtotal - ($dn->discount_amount ?? 0)),
                    'tax_amount' => -(float) $dn->tax_amount,
                    'type' => 'debit_note',
                ];
            }
        }

        $totalPpnMasukan = collect($ppnMasukan)->sum('tax_amount');
        $totalPpnKeluaran = collect($ppnKeluaran)->sum('tax_amount');

        // Ambil setoran PPN dalam periode ini
        $taxPaymentQuery = \App\Models\TaxPayment::where('tax_type', 'ppn')
            ->where('status', 'posted');

        if ($period_id) {
            $period = \App\Models\Period::find($period_id);
            if ($period) {
                $taxPaymentQuery->whereBetween('payment_date', [$period->start_date, $period->end_date]);
            }
        } elseif ($date_from && $date_to) {
            $taxPaymentQuery->whereBetween('payment_date', [$date_from, $date_to]);
        }

        $taxPayments = $taxPaymentQuery->get();
        $totalTaxPayments = (float) $taxPayments->sum('amount');

        return [
            'ppn_masukan' => $ppnMasukan,
            'ppn_keluaran' => $ppnKeluaran,
            'total_ppn_masukan' => $totalPpnMasukan,
            'total_ppn_keluaran' => $totalPpnKeluaran,
            'ppn_kurang_bayar' => max(0, $totalPpnKeluaran - $totalPpnMasukan - $totalTaxPayments),
            'ppn_lebih_bayar' => max(0, $totalPpnMasukan + $totalTaxPayments - $totalPpnKeluaran),
            'tax_payments' => $taxPayments,
            'total_tax_payments' => $totalTaxPayments,
            'ppn_outstanding' => $totalPpnKeluaran - $totalPpnMasukan - $totalTaxPayments,
        ];
    }

    public static function getPph23Report(?int $period_id = null, ?string $date_from = null, ?string $date_to = null): array
    {
        $pph23Rules = \App\Models\TaxRule::where('type', 'pph')
            ->where('method', 'withholding')
            ->where('is_active', true)
            ->get();

        if ($pph23Rules->isEmpty()) {
            return ['items' => [], 'total' => 0];
        }

        $dateCondition = function ($query) use ($period_id, $date_from, $date_to) {
            if ($period_id) {
                $period = \App\Models\Period::find($period_id);
                if ($period) {
                    return $query->whereBetween('date', [$period->start_date, $period->end_date]);
                }
            } elseif ($date_from && $date_to) {
                return $query->whereBetween('date', [$date_from, $date_to]);
            }
            return $query;
        };

        $items = [];

        $purchases = $dateCondition(\App\Models\Purchase::whereIn('status', ['posted', 'partially_paid', 'paid']))->get();
        foreach ($purchases as $purchase) {
            foreach ($purchase->taxes as $purchaseTax) {
                if ($pph23Rules->contains('id', $purchaseTax->tax_rule_id)) {
                    $baseAmount = (float) $purchase->subtotal - (float) $purchase->discount_amount;

                    $items[] = [
                        'date' => $purchase->date,
                        'number' => $purchase->number,
                        'contact' => $purchase->contact->name ?? '-',
                        'npwp' => $purchase->contact->npwp ?? '-',
                        'description' => $purchase->notes ?? 'Pembelian',
                        'base_amount' => $baseAmount,
                        'tax_rate' => (float) $purchaseTax->rate,
                        'tax_amount' => (float) $purchaseTax->tax_amount,
                    ];
                }
            }

            foreach ($purchase->payments as $payment) {
                if ((float) $payment->withholding_amount > 0) {
                    $items[] = [
                        'date' => $payment->date,
                        'number' => $payment->number ?? ('PAY-' . $payment->id),
                        'contact' => $purchase->contact->name ?? '-',
                        'npwp' => $purchase->contact->npwp ?? '-',
                        'description' => 'PPh 23 Dipotong dari Pembayaran',
                        'base_amount' => (float) $payment->amount,
                        'tax_rate' => 2.0,
                        'tax_amount' => (float) $payment->withholding_amount,
                    ];
                }
            }
        }

        $totalPph23 = collect($items)->sum('tax_amount');

        return [
            'items' => $items,
            'total' => $totalPph23,
        ];
    }

    public static function getAgingReport(string $type = 'receivable', ?string $as_of_date = null): array
    {
        if (!$as_of_date) {
            $as_of_date = now()->format('Y-m-d');
        }

        if ($type === 'receivable') {
            $records = DB::table('invoices')
                ->join('contacts', 'invoices.contact_id', '=', 'contacts.id')
                ->whereIn('invoices.status', ['posted', 'partially_paid'])
                ->whereRaw('(invoices.total - invoices.paid_amount) > 0.0001')
                ->selectRaw('
                    invoices.contact_id,
                    contacts.name as contact_name,
                    contacts.address,
                    contacts.phone,
                    invoices.id as record_id,
                    invoices.number as record_number,
                    invoices.due_date,
                    (invoices.total - invoices.paid_amount) as remaining_amount
                ')
                ->get();
        } else {
            $records = DB::table('purchases')
                ->join('contacts', 'purchases.contact_id', '=', 'contacts.id')
                ->whereIn('purchases.status', ['posted', 'partially_paid'])
                ->whereRaw('(purchases.total - purchases.paid_amount) > 0.0001')
                ->selectRaw('
                    purchases.contact_id,
                    contacts.name as contact_name,
                    contacts.address,
                    contacts.phone,
                    purchases.id as record_id,
                    purchases.number as record_number,
                    purchases.due_date,
                    (purchases.total - purchases.paid_amount) as remaining_amount
                ')
                ->get();
        }

        $contacts = [];
        foreach ($records as $record) {
            $asOfDate = Carbon::parse($as_of_date);
            $dueDate = Carbon::parse($record->due_date);
            $daysOverdue = $asOfDate->diffInDays($dueDate, false);

            if ($daysOverdue <= 0) {
                $bucket = 'current';
            } elseif ($daysOverdue <= 30) {
                $bucket = 'days_1_30';
            } elseif ($daysOverdue <= 60) {
                $bucket = 'days_31_60';
            } elseif ($daysOverdue <= 90) {
                $bucket = 'days_61_90';
            } else {
                $bucket = 'over_90';
            }

            if (!isset($contacts[$record->contact_id])) {
                $contacts[$record->contact_id] = [
                    'contact' => (object) [
                        'id' => $record->contact_id,
                        'name' => $record->contact_name,
                        'address' => $record->address,
                        'phone' => $record->phone,
                    ],
                    'current' => 0,
                    'days_1_30' => 0,
                    'days_31_60' => 0,
                    'days_61_90' => 0,
                    'over_90' => 0,
                    'total' => 0,
                    'details' => [],
                ];
            }

            $contacts[$record->contact_id][$bucket] += (float) $record->remaining_amount;
            $contacts[$record->contact_id]['total'] += (float) $record->remaining_amount;
            $contacts[$record->contact_id]['details'][] = [
                'id' => $record->record_id,
                'number' => $record->record_number,
                'due_date' => $record->due_date,
                'amount' => (float) $record->remaining_amount,
                'bucket' => $bucket,
                'days_overdue' => max(0, $daysOverdue),
            ];
        }

        $items = array_values($contacts);

        $totals = [
            'current' => collect($items)->sum('current'),
            'days_1_30' => collect($items)->sum('days_1_30'),
            'days_31_60' => collect($items)->sum('days_31_60'),
            'days_61_90' => collect($items)->sum('days_61_90'),
            'over_90' => collect($items)->sum('over_90'),
        ];
        $totals['total'] = array_sum($totals);

        $grandTotal = collect($items)->sum('total');

        return [
            'items' => $items,
            'totals' => $totals,
            'grand_total' => $grandTotal,
            'as_of_date' => $as_of_date,
            'type' => $type,
        ];
    }

    public static function getPph21Report(?int $period_id = null, ?string $date_from = null, ?string $date_to = null): array
    {
        $items = [];

        $query = \App\Models\TaxPayment::where('tax_type', 'pph21')
            ->where('status', 'posted')
            ->with(['period', 'createdBy']);

        if ($period_id) {
            $query->where('period_id', $period_id);
        } elseif ($date_from && $date_to) {
            $query->whereBetween('payment_date', [$date_from, $date_to]);
        }

        $taxPayments = $query->get();

        foreach ($taxPayments as $tp) {
            $period = $tp->period;
            $pDate = $period ? $period->start_date : $tp->payment_date;

            if ($date_from || $date_to) {
                $pd = $pDate instanceof Carbon ? $pDate->format('Y-m-d') : $pDate;
                if ($date_from && $pd < $date_from) continue;
                if ($date_to && $pd > $date_to) continue;
            }

            $items[] = [
                'payment_date' => $tp->payment_date,
                'reference' => $tp->reference ?? '-',
                'amount' => (float) $tp->amount,
                'notes' => $tp->notes ?? '',
                'document_number' => $tp->document_number,
            ];
        }

        $total = collect($items)->sum('amount');

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    public static function getPph4a2Report(?int $period_id = null, ?string $date_from = null, ?string $date_to = null): array
    {
        $items = [];

        $query = \App\Models\TaxPayment::where('tax_type', 'pph4a2')
            ->where('status', 'posted')
            ->with(['period', 'createdBy']);

        if ($period_id) {
            $query->where('period_id', $period_id);
        } elseif ($date_from && $date_to) {
            $query->whereBetween('payment_date', [$date_from, $date_to]);
        }

        $taxPayments = $query->get();

        foreach ($taxPayments as $tp) {
            $period = $tp->period;
            $pDate = $period ? $period->start_date : $tp->payment_date;

            if ($date_from || $date_to) {
                $pd = $pDate instanceof Carbon ? $pDate->format('Y-m-d') : $pDate;
                if ($date_from && $pd < $date_from) continue;
                if ($date_to && $pd > $date_to) continue;
            }

            $items[] = [
                'payment_date' => $tp->payment_date,
                'reference' => $tp->reference ?? '-',
                'amount' => (float) $tp->amount,
                'notes' => $tp->notes ?? '',
                'document_number' => $tp->document_number,
            ];
        }

        $total = collect($items)->sum('amount');

        return [
            'items' => $items,
            'total' => $total,
        ];
    }
}
