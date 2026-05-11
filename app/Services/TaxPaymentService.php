<?php

namespace App\Services;

use App\Exceptions\InvalidAccountException;
use App\Exceptions\PeriodClosedException;
use App\Models\Account;
use App\Models\Journal;
use App\Models\Period;
use App\Models\TaxPayment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TaxPaymentService
{
    public static function create(array $data): TaxPayment
    {
        $period = Period::findOrFail($data['period_id']);
        if ($period->is_closed) {
            throw new PeriodClosedException();
        }

        $paymentDate = \Carbon\Carbon::parse($data['payment_date']);
        if ($paymentDate->lt($period->start_date) || $paymentDate->gt($period->end_date)) {
            throw new \Exception('Tanggal pembayaran pajak berada di luar rentang periode aktif.');
        }

        $documentNumber = DocumentNumberService::generate('tax_payments', 'TAX', $data['payment_date']);

        return DB::transaction(function () use ($data, $documentNumber) {
            return TaxPayment::create([
                'period_id' => $data['period_id'],
                'account_id' => $data['account_id'],
                'document_number' => $documentNumber,
                'tax_type' => $data['tax_type'],
                'payment_date' => $data['payment_date'],
                'amount' => $data['amount'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'draft',
                'created_by' => Auth::id(),
            ]);
        });
    }

    public static function post(TaxPayment $taxPayment): TaxPayment
    {
        if ($taxPayment->status !== 'draft') {
            throw new InvalidAccountException("Setoran pajak dengan status '{$taxPayment->status}' tidak dapat diposting.");
        }

        $period = $taxPayment->period;
        if ($period->is_closed) {
            throw new PeriodClosedException();
        }

        if (!array_key_exists($taxPayment->tax_type, TaxPayment::TAX_ACCOUNT_CODES)) {
            throw new InvalidAccountException("Tipe pajak '{$taxPayment->tax_type}' tidak dikenali.");
        }

        $taxAccountCode = TaxPayment::TAX_ACCOUNT_CODES[$taxPayment->tax_type];
        $taxAccount = Account::where('code', $taxAccountCode)->first();
        if (!$taxAccount) {
            throw new InvalidAccountException("Akun hutang pajak dengan kode {$taxAccountCode} tidak ditemukan. Jalankan seeder terlebih dahulu.");
        }

        $cashAccount = $taxPayment->account;
        if (!$cashAccount || $cashAccount->is_header || !$cashAccount->is_active) {
            throw new InvalidAccountException('Akun kas/bank tidak valid.');
        }

        $amount = (float) $taxPayment->amount;

        return DB::transaction(function () use ($taxPayment, $period, $taxAccount, $cashAccount, $amount) {
            $journalLines = [
                [
                    'account_id' => $taxAccount->id,
                    'debit_amount' => $amount,
                    'credit_amount' => 0,
                    'description' => 'Setoran ' . (TaxPayment::TAX_TYPES[$taxPayment->tax_type] ?? $taxPayment->tax_type),
                ],
                [
                    'account_id' => $cashAccount->id,
                    'debit_amount' => 0,
                    'credit_amount' => $amount,
                    'description' => "Pembayaran: {$taxPayment->document_number}",
                ],
            ];

            $journalData = [
                'date' => $taxPayment->payment_date->format('Y-m-d'),
                'period_id' => $period->id,
                'description' => 'Setoran Pajak: ' . $taxPayment->document_number . ' (' . (TaxPayment::TAX_TYPES[$taxPayment->tax_type] ?? $taxPayment->tax_type) . ')',
                'source' => 'system',
                'type' => 'normal',
                'ref_type' => 'tax_payments',
                'ref_id' => $taxPayment->id,
                'created_by' => Auth::id(),
            ];

            $journal = JournalService::createJournal($journalData, $journalLines);

            $taxPayment->update([
                'journal_id' => $journal->id,
                'status' => 'posted',
                'posted_at' => now(),
            ]);

            return $taxPayment->fresh();
        });
    }

    public static function void(TaxPayment $taxPayment): TaxPayment
    {
        if ($taxPayment->status !== 'posted') {
            throw new InvalidAccountException("Setoran pajak dengan status '{$taxPayment->status}' tidak dapat dibatalkan.");
        }

        DB::transaction(function () use ($taxPayment) {
            if ($taxPayment->journal_id) {
                $journal = Journal::find($taxPayment->journal_id);
                if ($journal && $journal->type !== 'void') {
                    JournalService::voidJournal($journal, "Pembatalan setoran pajak {$taxPayment->document_number}");
                }
            }

            $taxPayment->update([
                'status' => 'void',
                'journal_id' => null,
            ]);
        });

        return $taxPayment->fresh();
    }
}
