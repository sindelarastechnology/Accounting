<?php

namespace App\Observers;

use App\Models\OpeningBalance;
use App\Models\Period;
use App\Models\Wallet;
use App\Services\JournalService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class WalletObserver
{
    public function created(Wallet $wallet): void
    {
        $balance = (float) $wallet->opening_balance;

        if ($balance <= 0 || is_null($wallet->equity_account_id) || is_null($wallet->account_id)) {
            return;
        }

        // Cek apakah sudah ada opening balance journal untuk wallet ini (mencegah duplikasi)
        $existingJournal = \App\Models\Journal::where('source', 'opening')
            ->where('ref_type', 'wallets')
            ->where('ref_id', $wallet->id)
            ->first();

        if ($existingJournal) {
            return;
        }

        $now = Carbon::now();
        $period = Period::where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->where('is_closed', false)
            ->first();

        if (!$period) {
            $period = Period::where('is_closed', false)
                ->orderBy('start_date')
                ->first();
        }

        if (!$period) {
            throw new \Exception(
                "Tidak dapat membuat jurnal saldo awal: tidak ada periode akuntansi yang tersedia. " .
                "Buat periode terlebih dahulu sebelum menambah wallet dengan saldo awal."
            );
        }

        DB::transaction(function () use ($wallet, $balance, $period) {
            // Buat jurnal untuk saldo awal wallet
            $journalLines = [
                [
                    'account_id' => $wallet->account_id,
                    'debit_amount' => $balance,
                    'credit_amount' => 0,
                    'wallet_id' => $wallet->id,
                    'description' => "Saldo awal {$wallet->name}",
                ],
                [
                    'account_id' => $wallet->equity_account_id,
                    'debit_amount' => 0,
                    'credit_amount' => $balance,
                    'description' => "Lawan saldo awal {$wallet->name}",
                ],
            ];

            $journalData = [
                'date' => $period->start_date,
                'period_id' => $period->id,
                'description' => "Saldo Awal: {$wallet->name}",
                'source' => 'opening',
                'type' => 'opening',
                'ref_type' => 'wallets',
                'ref_id' => $wallet->id,
                'created_by' => auth()->id(),
            ];

            JournalService::createJournal($journalData, $journalLines);

            // Tulis ke opening_balances agar perhitungan balance (getAccountBalance) bisa mengambilnya
            OpeningBalance::create([
                'period_id' => $period->id,
                'account_id' => $wallet->account_id,
                'amount' => $balance,
                'position' => 'debit',
                'wallet_id' => $wallet->id,
            ]);

            OpeningBalance::create([
                'period_id' => $period->id,
                'account_id' => $wallet->equity_account_id,
                'amount' => $balance,
                'position' => 'credit',
            ]);
        });
    }
}
