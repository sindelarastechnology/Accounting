<?php

namespace App\Services;

use App\Helpers\AccountResolver;
use App\Models\Account;
use App\Models\Period;
use Illuminate\Support\Facades\DB;

class RatioService
{
    /**
     * Calculate all financial ratios for a given period.
     */
    public static function calculateAll(?int $periodId = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $period = null;
        if ($periodId) {
            $period = Period::find($periodId);
            if ($period) {
                $dateFrom = $period->start_date->format('Y-m-d');
                $dateTo = $period->end_date->format('Y-m-d');
            }
        }

        $bs = JournalService::getBalanceSheet($periodId, $dateTo);
        $is = JournalService::getIncomeStatement($periodId, $dateFrom, $dateTo);

        $totalAssets = $bs['assets']['total'];
        $currentAssets = $bs['assets']['total_current'];
        $totalLiabilities = $bs['liabilities']['total'];
        $currentLiabilities = $bs['liabilities']['total_current'];
        $totalEquity = $bs['equity']['total'];

        $revenue = $is['total_revenue'];
        $cogs = $is['total_cogs'];
        $grossProfit = $is['gross_profit'];
        $netIncome = $is['net_income'];
        $operatingProfit = $is['operating_profit'];

        // === LIQUIDITY RATIOS ===
        $currentRatio = $currentLiabilities > 0 ? $currentAssets / $currentLiabilities : null;

        // Quick Ratio (Current Assets - Inventory - Prepaids) / Current Liabilities
        $inventoryAccountId = AccountResolver::inventory();
        $inventoryBalance = JournalService::getAccountBalance($inventoryAccountId, $periodId)['balance'];
        $quickAssets = $currentAssets - max(0, (float) $inventoryBalance);
        $quickRatio = $currentLiabilities > 0 ? $quickAssets / $currentLiabilities : null;

        // Cash Ratio
        $cashAccountIds = \App\Models\Wallet::active()->pluck('account_id')->toArray();
        $cashBalance = 0;
        foreach ($cashAccountIds as $accId) {
            $bal = JournalService::getAccountBalance($accId, $periodId);
            $cashBalance += (float) ($bal['balance'] ?? 0);
        }
        $cashRatio = $currentLiabilities > 0 ? $cashBalance / $currentLiabilities : null;

        // === PROFITABILITY RATIOS ===
        $grossProfitMargin = $revenue > 0 ? ($grossProfit / $revenue) * 100 : null;
        $netProfitMargin = $revenue > 0 ? ($netIncome / $revenue) * 100 : null;
        $operatingMargin = $revenue > 0 ? ($operatingProfit / $revenue) * 100 : null;
        $roa = $totalAssets > 0 ? ($netIncome / $totalAssets) * 100 : null;
        $roe = $totalEquity > 0 ? ($netIncome / $totalEquity) * 100 : null;

        // === SOLVENCY RATIOS ===
        $debtToEquity = $totalEquity > 0 ? ($totalLiabilities / $totalEquity) * 100 : null;
        $debtRatio = $totalAssets > 0 ? ($totalLiabilities / $totalAssets) * 100 : null;

        // Interest Coverage Ratio (EBIT / Interest Expense)
        $ebit = $is['income_before_tax'] ?? $netIncome;
        $interestExpense = self::getInterestExpense($periodId, $dateFrom, $dateTo);
        $interestCoverage = $interestExpense > 0 ? $ebit / $interestExpense : null;

        // Days in period for efficiency ratios
        $daysInPeriod = 365;
        if ($period) {
            $daysInPeriod = (int) $period->start_date->diffInDays($period->end_date) ?: 365;
        }

        // === EFFICIENCY RATIOS ===
        // AR Turnover = Revenue / Average AR
        $arAccountId = AccountResolver::receivable();
        $arBalance = JournalService::getAccountBalance($arAccountId, $periodId)['balance'];
        $arTurnover = (float) $arBalance > 0 ? $revenue / (float) $arBalance : null;
        $arDays = $arTurnover !== null && $arTurnover > 0 ? $daysInPeriod / $arTurnover : null;

        // Inventory Turnover = COGS / Average Inventory
        $invTurnover = (float) $inventoryBalance > 0 ? $cogs / (float) $inventoryBalance : null;
        $invDays = $invTurnover !== null && $invTurnover > 0 ? $daysInPeriod / $invTurnover : null;

        // AP Turnover = Purchases / Average AP
        $apAccountId = AccountResolver::payable();
        $apBalance = JournalService::getAccountBalance($apAccountId, $periodId)['balance'];
        $apTurnover = (float) $apBalance > 0 ? $cogs / (float) $apBalance : null;
        $apDays = $apTurnover !== null && $apTurnover > 0 ? $daysInPeriod / $apTurnover : null;

        // Asset Turnover = Revenue / Total Assets
        $assetTurnover = $totalAssets > 0 ? $revenue / $totalAssets : null;

        $periodLabel = $period ? $period->name : ($dateFrom && $dateTo ? "$dateFrom - $dateTo" : 'All Time');

        return [
            'period_label' => $periodLabel,
            'liquidity' => [
                'current_ratio' => ['value' => $currentRatio, 'label' => 'Current Ratio', 'unit' => 'x', 'interpretation' => self::interpretCurrentRatio($currentRatio)],
                'quick_ratio' => ['value' => $quickRatio, 'label' => 'Quick Ratio (Acid Test)', 'unit' => 'x', 'interpretation' => self::interpretQuickRatio($quickRatio)],
                'cash_ratio' => ['value' => $cashRatio, 'label' => 'Cash Ratio', 'unit' => 'x', 'interpretation' => self::interpretCashRatio($cashRatio)],
            ],
            'profitability' => [
                'gross_profit_margin' => ['value' => $grossProfitMargin, 'label' => 'Gross Profit Margin', 'unit' => '%', 'interpretation' => self::interpretPercentage($grossProfitMargin)],
                'net_profit_margin' => ['value' => $netProfitMargin, 'label' => 'Net Profit Margin', 'unit' => '%', 'interpretation' => self::interpretPercentage($netProfitMargin)],
                'operating_margin' => ['value' => $operatingMargin, 'label' => 'Operating Margin', 'unit' => '%', 'interpretation' => self::interpretPercentage($operatingMargin)],
                'roa' => ['value' => $roa, 'label' => 'ROA (Return on Assets)', 'unit' => '%', 'interpretation' => self::interpretPercentage($roa)],
                'roe' => ['value' => $roe, 'label' => 'ROE (Return on Equity)', 'unit' => '%', 'interpretation' => self::interpretPercentage($roe)],
            ],
            'solvency' => [
                'debt_to_equity' => ['value' => $debtToEquity, 'label' => 'Debt to Equity Ratio (DER)', 'unit' => '%', 'interpretation' => self::interpretDer($debtToEquity)],
                'debt_ratio' => ['value' => $debtRatio, 'label' => 'Debt Ratio', 'unit' => '%', 'interpretation' => self::interpretDebtRatio($debtRatio)],
                'interest_coverage' => ['value' => $interestCoverage, 'label' => 'Interest Coverage Ratio', 'unit' => 'x', 'interpretation' => self::interpretInterestCoverage($interestCoverage)],
            ],
            'efficiency' => [
                'ar_turnover' => ['value' => $arTurnover, 'label' => 'AR Turnover', 'unit' => 'x', 'interpretation' => null],
                'ar_days' => ['value' => $arDays, 'label' => 'AR Days (Collection Period)', 'unit' => 'hari', 'interpretation' => null],
                'inventory_turnover' => ['value' => $invTurnover, 'label' => 'Inventory Turnover', 'unit' => 'x', 'interpretation' => null],
                'inventory_days' => ['value' => $invDays, 'label' => 'Inventory Days', 'unit' => 'hari', 'interpretation' => null],
                'ap_turnover' => ['value' => $apTurnover, 'label' => 'AP Turnover', 'unit' => 'x', 'interpretation' => null],
                'ap_days' => ['value' => $apDays, 'label' => 'AP Days (Payment Period)', 'unit' => 'hari', 'interpretation' => null],
                'asset_turnover' => ['value' => $assetTurnover, 'label' => 'Asset Turnover', 'unit' => 'x', 'interpretation' => null],
            ],
        ];
    }

    /**
     * Get total interest expense (beban bunga) for a period.
     * Queries expense accounts with code starting with 65 (financial costs).
     */
    private static function getInterestExpense(?int $periodId, ?string $dateFrom, ?string $dateTo): float
    {
        $query = DB::table('journal_lines')
            ->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
            ->join('accounts', 'journal_lines.account_id', '=', 'accounts.id')
            ->where('journals.type', '!=', 'void')
            ->where('accounts.category', 'expense')
            ->where(function ($q) {
                $q->where('accounts.code', 'like', '65%')
                    ->orWhere('accounts.name', 'like', '%bunga%')
                    ->orWhere('accounts.name', 'like', '%interest%')
                    ->orWhere('accounts.name', 'like', '%bank%')
                    ->orWhere('accounts.code', 'like', '8100%');
            });

        if ($periodId) {
            $query->where('journals.period_id', $periodId);
        } elseif ($dateFrom && $dateTo) {
            $query->whereBetween('journals.date', [$dateFrom, $dateTo]);
        }

        return (float) $query->sum('journal_lines.debit_amount');
    }

    private static function interpretCurrentRatio(?float $val): ?string
    {
        if ($val === null) return null;
        if ($val >= 2) return 'Baik (> 2x)';
        if ($val >= 1.5) return 'Cukup (1.5 - 2x)';
        if ($val >= 1) return 'Kurang (1 - 1.5x)';
        return 'Rendah (< 1x)';
    }

    private static function interpretQuickRatio(?float $val): ?string
    {
        if ($val === null) return null;
        if ($val >= 1) return 'Baik (> 1x)';
        if ($val >= 0.5) return 'Cukup (0.5 - 1x)';
        return 'Rendah (< 0.5x)';
    }

    private static function interpretCashRatio(?float $val): ?string
    {
        if ($val === null) return null;
        if ($val >= 0.5) return 'Baik (> 0.5x)';
        if ($val >= 0.2) return 'Cukup (0.2 - 0.5x)';
        return 'Rendah (< 0.2x)';
    }

    private static function interpretPercentage(?float $val): ?string
    {
        if ($val === null) return null;
        if ($val > 20) return 'Sangat Baik';
        if ($val > 10) return 'Baik';
        if ($val > 5) return 'Cukup';
        if ($val > 0) return 'Rendah';
        return 'Negatif';
    }

    private static function interpretDer(?float $val): ?string
    {
        if ($val === null) return null;
        if ($val <= 50) return 'Konservatif (< 50%)';
        if ($val <= 100) return 'Moderat (50% - 100%)';
        if ($val <= 200) return 'Agresif (100% - 200%)';
        return 'Berisiko (> 200%)';
    }

    private static function interpretDebtRatio(?float $val): ?string
    {
        if ($val === null) return null;
        if ($val <= 40) return 'Baik (< 40%)';
        if ($val <= 60) return 'Cukup (40% - 60%)';
        return 'Tinggi (> 60%)';
    }

    private static function interpretInterestCoverage(?float $val): ?string
    {
        if ($val === null) return null;
        if ($val >= 3) return 'Aman (> 3x)';
        if ($val >= 1.5) return 'Cukup (1.5 - 3x)';
        return 'Berisiko (< 1.5x)';
    }
}
