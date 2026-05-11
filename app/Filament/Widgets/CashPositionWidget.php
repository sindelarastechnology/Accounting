<?php

namespace App\Filament\Widgets;

use App\Models\Wallet;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CashPositionWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $wallets = Wallet::active()->with('account')->get();

        $stats = [];
        foreach ($wallets as $wallet) {
            $balance = 0;
            if ($wallet->account) {
                $bal = \App\Services\JournalService::getAccountBalanceUpToDate($wallet->account->id, now()->format('Y-m-d'));
                $balance = $bal['balance'];
            }

            $stats[] = Stat::make($wallet->name, rupiah($balance))
                ->description('Saldo saat ini')
                ->color($balance >= 0 ? 'success' : 'danger')
                ->icon('heroicon-o-banknotes');
        }

        return $stats ?: [Stat::make('Info', 'Tidak ada wallet aktif')->icon('heroicon-o-information-circle')];
    }
}
