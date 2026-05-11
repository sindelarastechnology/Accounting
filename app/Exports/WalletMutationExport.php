<?php

namespace App\Exports;

use App\Models\Period;
use App\Models\Wallet;
use App\Services\JournalService;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class WalletMutationExport implements WithMultipleSheets
{
    protected ?int $wallet_id;
    protected ?string $date_from;
    protected ?string $date_to;
    protected ?int $period_id;

    public function __construct(?int $wallet_id = null, ?string $date_from = null, ?string $date_to = null, ?int $period_id = null)
    {
        $this->wallet_id = $wallet_id;
        $this->date_from = $date_from;
        $this->date_to = $date_to;
        $this->period_id = $period_id;
    }

    public function sheets(): array
    {
        $dateFrom = $this->date_from;
        $dateTo = $this->date_to;

        if (!$dateFrom && !$dateTo && $this->period_id) {
            $period = Period::find($this->period_id);
            if ($period) {
                $dateFrom = $period->start_date instanceof \Carbon\Carbon ? $period->start_date->format('Y-m-d') : $period->start_date;
                $dateTo = $period->end_date instanceof \Carbon\Carbon ? $period->end_date->format('Y-m-d') : $period->end_date;
            }
        }

        if (!$dateFrom) $dateFrom = now()->startOfMonth()->format('Y-m-d');
        if (!$dateTo) $dateTo = now()->format('Y-m-d');

        $wallets = Wallet::active()->with('account');
        if ($this->wallet_id) {
            $wallets->where('id', $this->wallet_id);
        }
        $wallets = $wallets->get();

        $sheets = [];
        foreach ($wallets as $wallet) {
            if (!$wallet->account) continue;
            $sheets[] = new WalletMutationSheet($wallet, $dateFrom, $dateTo);
        }

        return $sheets ?: [new WalletMutationSheet(null, $dateFrom, $dateTo)];
    }
}
