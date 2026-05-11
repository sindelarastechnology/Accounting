<?php

namespace App\Services;

use App\Exceptions\InvalidAccountException;
use App\Exceptions\PeriodClosedException;
use App\Models\Account;
use App\Models\FundTransfer;
use App\Models\Journal;
use App\Models\Period;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FundTransferService
{
    public static function createTransfer(array $data): FundTransfer
    {
        if ($data['from_wallet_id'] === $data['to_wallet_id']) {
            throw new InvalidAccountException('Rekening asal dan tujuan tidak boleh sama.');
        }

        if ((float) $data['amount'] <= 0) {
            throw new InvalidAccountException('Jumlah transfer harus lebih dari 0.');
        }

        if (!isset($data['period_id'])) {
            $period = Period::where('is_closed', false)
                ->where('start_date', '<=', $data['date'])
                ->where('end_date', '>=', $data['date'])
                ->firstOrFail();
            $data['period_id'] = $period->id;
        } else {
            $period = Period::findOrFail($data['period_id']);
        }
        if ($period->is_closed) {
            throw new PeriodClosedException();
        }

        $transferDate = Carbon::parse($data['date']);
        if ($transferDate->lt($period->start_date) || $transferDate->gt($period->end_date)) {
            throw new \Exception('Tanggal transfer berada di luar rentang periode aktif.');
        }

        $number = \App\Services\DocumentNumberService::generate('fund_transfers', 'TRF', $data['date']);

        return FundTransfer::create([
            'number' => $number,
            'period_id' => $data['period_id'],
            'date' => $data['date'],
            'from_wallet_id' => $data['from_wallet_id'],
            'to_wallet_id' => $data['to_wallet_id'],
            'amount' => $data['amount'],
            'fee_amount' => $data['fee_amount'] ?? 0,
            'fee_account_id' => $data['fee_account_id'] ?? null,
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => 'draft',
            'created_by' => $data['created_by'] ?? Auth::id(),
        ]);
    }

    public static function postTransfer(FundTransfer $transfer): FundTransfer
    {
        if ($transfer->status !== 'draft') {
            throw new InvalidAccountException("Transfer dengan status '{$transfer->status}' tidak dapat diposting.");
        }

        $period = $transfer->period;
        if ($period->is_closed) {
            throw new PeriodClosedException();
        }

        $fromWallet = $transfer->fromWallet;
        $toWallet = $transfer->toWallet;

        if (!$fromWallet || !$fromWallet->account_id) {
            throw new InvalidAccountException('Rekening asal tidak valid.');
        }
        if (!$toWallet || !$toWallet->account_id) {
            throw new InvalidAccountException('Rekening tujuan tidak valid.');
        }

        return DB::transaction(function () use ($transfer, $period, $fromWallet, $toWallet) {
            $amount = (float) $transfer->amount;
            $feeAmount = (float) $transfer->fee_amount;
            $totalDebit = $amount + $feeAmount;

            $journalLines = [];

            // Rekening tujuan bertambah
            $journalLines[] = [
                'account_id' => $toWallet->account_id,
                'debit_amount' => $amount,
                'credit_amount' => 0,
                'description' => 'Transfer masuk: ' . $transfer->number,
                'wallet_id' => $toWallet->id,
            ];

            // Biaya transfer (jika ada)
            if ($feeAmount > 0 && $transfer->fee_account_id) {
                $journalLines[] = [
                    'account_id' => $transfer->fee_account_id,
                    'debit_amount' => $feeAmount,
                    'credit_amount' => 0,
                    'description' => 'Biaya transfer: ' . $transfer->number,
                ];
            }

            // Rekening asal berkurang
            $journalLines[] = [
                'account_id' => $fromWallet->account_id,
                'debit_amount' => 0,
                'credit_amount' => $totalDebit,
                'description' => 'Transfer keluar: ' . $transfer->number,
                'wallet_id' => $fromWallet->id,
            ];

            $journalData = [
                'date' => $transfer->date,
                'period_id' => $period->id,
                'description' => 'Transfer Kas: ' . $fromWallet->name . ' → ' . $toWallet->name . ' (' . $transfer->number . ')',
                'source' => 'transfer',
                'type' => 'normal',
                'ref_type' => 'fund_transfers',
                'ref_id' => $transfer->id,
                'created_by' => Auth::id(),
            ];

            $journal = JournalService::createJournal($journalData, $journalLines);

            $transfer->update([
                'status' => 'posted',
                'journal_id' => $journal->id,
            ]);

            return $transfer->fresh();
        });
    }

    public static function cancelTransfer(FundTransfer $transfer): void
    {
        if ($transfer->status === 'cancelled') {
            throw new InvalidAccountException('Transfer sudah dibatalkan.');
        }

        DB::transaction(function () use ($transfer) {
            if ($transfer->journal_id) {
                $journal = Journal::find($transfer->journal_id);
                if ($journal && $journal->type !== 'void') {
                    JournalService::voidJournal($journal, "Pembatalan transfer {$transfer->number}");
                }
            }

            $transfer->update(['status' => 'cancelled']);
        });
    }
}
