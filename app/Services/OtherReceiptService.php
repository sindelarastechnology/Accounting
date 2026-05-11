<?php

namespace App\Services;

use App\Exceptions\InvalidAccountException;
use App\Exceptions\PeriodClosedException;
use App\Models\Account;
use App\Models\Journal;
use App\Models\OtherReceipt;
use App\Models\Period;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OtherReceiptService
{
    public static function createReceipt(array $data): OtherReceipt
    {
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

        $receiptDate = Carbon::parse($data['date']);
        if ($receiptDate->lt($period->start_date) || $receiptDate->gt($period->end_date)) {
            throw new \Exception('Tanggal penerimaan berada di luar rentang periode aktif.');
        }

        if ((float) $data['amount'] <= 0) {
            throw new InvalidAccountException('Jumlah penerimaan harus lebih dari 0.');
        }

        $number = \App\Services\DocumentNumberService::generate('other_receipts', 'RCP', $data['date']);

        return OtherReceipt::create([
            'number' => $number,
            'period_id' => $data['period_id'],
            'date' => $data['date'],
            'wallet_id' => $data['wallet_id'],
            'contact_id' => $data['contact_id'] ?? null,
            'receipt_type' => $data['receipt_type'],
            'amount' => $data['amount'],
            'credit_account_id' => $data['credit_account_id'],
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => 'draft',
            'created_by' => $data['created_by'] ?? Auth::id(),
        ]);
    }

    public static function postReceipt(OtherReceipt $receipt): OtherReceipt
    {
        if ($receipt->status !== 'draft') {
            throw new InvalidAccountException("Penerimaan dengan status '{$receipt->status}' tidak dapat diposting.");
        }

        $period = $receipt->period;
        if ($period->is_closed) {
            throw new PeriodClosedException();
        }

        $wallet = $receipt->wallet;
        if (!$wallet || !$wallet->account_id) {
            throw new InvalidAccountException('Wallet tidak valid atau tidak memiliki akun.');
        }

        $creditAccount = $receipt->creditAccount;
        if (!$creditAccount || $creditAccount->is_header || !$creditAccount->is_active) {
            throw new InvalidAccountException("Akun kredit '{$receipt->creditAccount->name}' tidak valid.");
        }

        return DB::transaction(function () use ($receipt, $period, $wallet, $creditAccount) {
            $journalLines = [];

            // Kas/Bank bertambah (debit)
            $journalLines[] = [
                'account_id' => $wallet->account_id,
                'debit_amount' => (float) $receipt->amount,
                'credit_amount' => 0,
                'description' => $receipt->number . ': ' . ($receipt->receipt_type),
                'wallet_id' => $wallet->id,
            ];

            // Akun pasangan bertambah (kredit)
            $journalLines[] = [
                'account_id' => $creditAccount->id,
                'debit_amount' => 0,
                'credit_amount' => (float) $receipt->amount,
                'description' => $receipt->number . ': ' . ($receipt->notes ?? $receipt->receipt_type),
            ];

            $journalData = [
                'date' => $receipt->date,
                'period_id' => $period->id,
                'description' => 'Kas Masuk Lainnya: ' . $receipt->number . ' (' . (OtherReceipt::receiptTypes()[$receipt->receipt_type] ?? $receipt->receipt_type) . ')',
                'source' => 'other_receipt',
                'type' => 'normal',
                'ref_type' => 'other_receipts',
                'ref_id' => $receipt->id,
                'created_by' => Auth::id(),
            ];

            $journal = JournalService::createJournal($journalData, $journalLines);

            $receipt->update([
                'status' => 'posted',
                'journal_id' => $journal->id,
            ]);

            return $receipt->fresh();
        });
    }

    public static function cancelReceipt(OtherReceipt $receipt): void
    {
        if ($receipt->status === 'cancelled') {
            throw new InvalidAccountException('Penerimaan sudah dibatalkan.');
        }

        DB::transaction(function () use ($receipt) {
            if ($receipt->journal_id) {
                $journal = Journal::find($receipt->journal_id);
                if ($journal && $journal->type !== 'void') {
                    JournalService::voidJournal($journal, "Pembatalan penerimaan {$receipt->number}");
                }
            }

            $receipt->update(['status' => 'cancelled']);
        });
    }
}
