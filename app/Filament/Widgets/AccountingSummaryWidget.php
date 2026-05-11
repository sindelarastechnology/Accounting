<?php

namespace App\Filament\Widgets;

use App\Services\JournalService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AccountingSummaryWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $periodId = \App\Models\Period::where('is_closed', false)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->value('id');

        $incomeData = JournalService::getIncomeStatement($periodId);

        $arBalance = 0;
        $arAccountId = \App\Models\Account::where('code', '1300-00-020')->value('id');
        if ($arAccountId) {
            $bal = JournalService::getAccountBalance($arAccountId, $periodId);
            $arBalance = $bal['balance'];
        }

        return [
            Stat::make('Total Pendapatan', rupiah($incomeData['total_revenue']))
                ->description('Bulan ini')
                ->color('success')
                ->icon('heroicon-o-arrow-trending-up'),
            Stat::make('Total Beban', rupiah($incomeData['total_expenses']))
                ->description('Bulan ini')
                ->color('danger')
                ->icon('heroicon-o-arrow-trending-down'),
            Stat::make('Laba Bersih', rupiah($incomeData['net_income']))
                ->description($incomeData['net_income'] < 0 ? 'Rugi' : 'Bulan ini')
                ->color($incomeData['net_income'] < 0 ? 'danger' : 'success')
                ->icon('heroicon-o-currency-dollar'),
            Stat::make('Piutang Outstanding', rupiah($arBalance))
                ->description('Belum dibayar')
                ->color('warning')
                ->icon('heroicon-o-credit-card'),
        ];
    }
}
