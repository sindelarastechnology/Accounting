<?php

namespace App\Services;

use App\Exceptions\InvalidAccountException;
use App\Exceptions\InvalidOperationException;
use App\Exceptions\PeriodClosedException;
use App\Helpers\AccountResolver;
use App\Models\Account;
use App\Models\Invoice;
use App\Models\Journal;
use App\Models\Payment;
use App\Models\Period;
use App\Models\Purchase;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public static function createPayment(array $data): Payment
    {
        $period = Period::findOrFail($data['period_id']);
        if ($period->is_closed) {
            throw new PeriodClosedException();
        }

        $paymentDate = Carbon::parse($data['date']);
        if ($paymentDate->lt($period->start_date) || $paymentDate->gt($period->end_date)) {
            throw new \Exception(
                "Tanggal pembayaran berada di luar rentang periode aktif."
            );
        }

        $payable = self::resolvePayable($data['payable_type'], $data['payable_id']);

        if ($payable->status === 'paid') {
            $label = $data['payable_type'] === 'invoices' ? 'Invoice' : 'Purchase Order';
            throw new InvalidOperationException(
                "Tidak bisa menambah pembayaran: {$label} sudah lunas."
            );
        }

        if ($data['amount'] > $payable->due_amount + 0.0001) {
            throw new InvalidOperationException(
                'Jumlah pembayaran (Rp ' . number_format($data['amount'], 0, ',', '.') .
                ') melebihi sisa tagihan (Rp ' . number_format($payable->due_amount, 0, ',', '.') . ').'
            );
        }

        return DB::transaction(function () use ($data, $payable) {
            $number = \App\Services\DocumentNumberService::generate('payments', 'PAY', $data['date']);

            $payment = Payment::create([
                'number' => $number,
                'payable_type' => $data['payable_type'],
                'payable_id' => $data['payable_id'],
                'period_id' => $data['period_id'],
                'wallet_id' => $data['wallet_id'],
                'date' => $data['date'],
                'amount' => $data['amount'],
                'withholding_amount' => $data['withholding_amount'] ?? 0,
                'method' => $data['method'] ?? 'cash',
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => $data['status'] ?? 'pending',
                'created_by' => $data['created_by'] ?? Auth::id(),
            ]);

            return $payment;
        });
    }

    public static function verifyPayment(Payment $payment): Journal
    {
        if ($payment->status !== 'pending') {
            throw new InvalidAccountException("Pembayaran dengan status '{$payment->status}' tidak dapat diverifikasi.");
        }

        $period = $payment->period;
        if ($period->is_closed) {
            throw new PeriodClosedException();
        }

        $payable = null;
        if ($payment->payable_type === 'invoices') {
            $payable = Invoice::findOrFail($payment->payable_id);
        } elseif ($payment->payable_type === 'purchases') {
            $payable = Purchase::findOrFail($payment->payable_id);
        } else {
            throw new InvalidAccountException('Tipe payable tidak valid.');
        }

        $wallet = $payment->wallet;

        $journalLines = [];
        $netAmount = (float) $payment->amount - (float) $payment->withholding_amount;

        if ($payment->payable_type === 'invoices') {
            $arAccountId = AccountResolver::receivable();

            $journalLines[] = [
                'account_id' => $wallet->account_id,
                'debit_amount' => $netAmount,
                'credit_amount' => 0,
                'description' => "Penerimaan: {$payment->number}",
                'wallet_id' => $wallet->id,
            ];

            if ((float) $payment->withholding_amount > 0) {
                $pphPrepaidId = AccountResolver::pphPrepaid();

                $journalLines[] = [
                    'account_id' => $pphPrepaidId,
                    'debit_amount' => (float) $payment->withholding_amount,
                    'credit_amount' => 0,
                    'description' => "PPh dipotong customer: {$payment->number}",
                ];
            }

            $journalLines[] = [
                'account_id' => $arAccountId,
                'debit_amount' => 0,
                'credit_amount' => (float) $payment->amount,
                'description' => "Pelunasan piutang: {$payable->number}",
            ];

        } elseif ($payment->payable_type === 'purchases') {
            $apAccountId = AccountResolver::payable();

            $journalLines[] = [
                'account_id' => $apAccountId,
                'debit_amount' => (float) $payment->amount,
                'credit_amount' => 0,
                'description' => "Pelunasan hutang: {$payable->number}",
            ];

            $journalLines[] = [
                'account_id' => $wallet->account_id,
                'debit_amount' => 0,
                'credit_amount' => $netAmount,
                'description' => "Pengeluaran: {$payment->number}",
                'wallet_id' => $wallet->id,
            ];

            if ((float) $payment->withholding_amount > 0) {
                $withholdingAccountId = AccountResolver::pphPayable();

                $journalLines[] = [
                    'account_id' => $withholdingAccountId,
                    'debit_amount' => 0,
                    'credit_amount' => (float) $payment->withholding_amount,
                    'description' => "PPh dipotong supplier: {$payment->number}",
                ];
            }
        }

        $journal = DB::transaction(function () use ($payment, $period, $journalLines) {
            $journalData = [
                'date' => $payment->date,
                'period_id' => $period->id,
                'description' => $payment->payable_type === 'invoices'
                    ? "Penerimaan Pembayaran: {$payment->number}"
                    : "Pengeluaran Pembayaran: {$payment->number}",
                'source' => 'payment',
                'type' => 'normal',
                'ref_type' => 'payments',
                'ref_id' => $payment->id,
                'created_by' => Auth::id(),
            ];

            $journal = JournalService::createJournal($journalData, $journalLines);

            $payment->update([
                'status' => 'verified',
                'verified_by' => Auth::id(),
                'verified_at' => now(),
                'journal_id' => $journal->id,
            ]);

            $payable = $payment->payable_type === 'invoices'
                ? Invoice::findOrFail($payment->payable_id)
                : Purchase::findOrFail($payment->payable_id);

            $newPaidAmount = (float) $payable->paid_amount + (float) $payment->amount;
            $newDueAmount = (float) $payable->total - $newPaidAmount;

            $newStatus = $payable->status;
            if ($newDueAmount <= 0.0001) {
                $newStatus = 'paid';
            } elseif ($newPaidAmount > 0) {
                $newStatus = 'partially_paid';
            }

            $payable->update([
                'paid_amount' => $newPaidAmount,
                'due_amount' => max(0, $newDueAmount),
                'status' => $newStatus,
            ]);

            return $journal;
        });

        return $journal;
    }

    public static function cancelPayment(Payment $payment): void
    {
        if ($payment->status === 'cancelled') {
            throw new InvalidAccountException('Pembayaran sudah dibatalkan.');
        }

        DB::transaction(function () use ($payment) {
            if ($payment->journal_id) {
                $journal = Journal::find($payment->journal_id);
                if ($journal && $journal->type !== 'void') {
                    JournalService::voidJournal($journal, "Pembatalan pembayaran {$payment->number}");
                }
            }

            $payable = $payment->payable_type === 'invoices'
                ? Invoice::findOrFail($payment->payable_id)
                : Purchase::findOrFail($payment->payable_id);

            $newPaidAmount = max(0, (float) $payable->paid_amount - (float) $payment->amount);
            $newDueAmount = (float) $payable->total - $newPaidAmount;

            if ($newDueAmount <= 0.0001) {
                $newStatus = 'paid';
            } elseif ($newPaidAmount > 0.0001) {
                $newStatus = 'partially_paid';
            } else {
                $newStatus = 'posted';
            }

            $payable->update([
                'paid_amount' => $newPaidAmount,
                'due_amount' => max(0, $newDueAmount),
                'status' => $newStatus,
            ]);

            $payment->update([
                'status' => 'cancelled',
            ]);
        });
    }

    private static function resolvePayable(string $type, int $id): Invoice|Purchase
    {
        if ($type === 'invoices') {
            $payable = Invoice::findOrFail($id);
            if ($payable->status === 'cancelled') {
                throw new InvalidAccountException('Invoice sudah dibatalkan.');
            }
        } elseif ($type === 'purchases') {
            $payable = Purchase::findOrFail($id);
            if ($payable->status === 'cancelled') {
                throw new InvalidAccountException('Pembelian sudah dibatalkan.');
            }
        } else {
            throw new InvalidAccountException('Tipe payable harus invoices atau purchases.');
        }
        return $payable;
    }
}
