<?php

namespace App\Filament\Widgets;

use App\Services\JournalService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AgingSummaryWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected function getStats(): array
    {
        $arData = JournalService::getAgingReport('receivable', now()->format('Y-m-d'));
        $apData = JournalService::getAgingReport('payable', now()->format('Y-m-d'));

        $arOverdue = ($arData['totals']['days_31_60'] ?? 0) + ($arData['totals']['days_61_90'] ?? 0) + ($arData['totals']['over_90'] ?? 0);
        $apOverdue = ($apData['totals']['days_31_60'] ?? 0) + ($apData['totals']['days_61_90'] ?? 0) + ($apData['totals']['over_90'] ?? 0);

        return [
            Stat::make('Total Piutang', rupiah($arData['grand_total']))
                ->description('Semua piutang outstanding')
                ->color('info')
                ->icon('heroicon-o-credit-card'),
            Stat::make('Piutang Overdue (>30 hari)', rupiah($arOverdue))
                ->description('Sudah lewat jatuh tempo')
                ->color($arOverdue > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation-triangle'),
            Stat::make('Total Hutang', rupiah($apData['grand_total']))
                ->description('Semua hutang outstanding')
                ->color('warning')
                ->icon('heroicon-o-banknotes'),
            Stat::make('Hutang Overdue (>30 hari)', rupiah($apOverdue))
                ->description('Sudah lewat jatuh tempo')
                ->color($apOverdue > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation-triangle'),
        ];
    }
}
