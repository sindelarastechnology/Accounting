<?php

namespace App\Services;

use App\Exceptions\InvalidAccountException;
use App\Exceptions\PeriodClosedException;
use App\Models\Account;
use App\Models\Expense;
use App\Models\Journal;
use App\Models\Period;
use App\Models\Setting;
use App\Models\TaxRule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public static function createExpense(array $data): Expense
    {
        $period = Period::findOrFail($data['period_id']);
        if ($period->is_closed) {
            throw new PeriodClosedException();
        }

        $expenseDate = Carbon::parse($data['date']);
        if ($expenseDate->lt($period->start_date) || $expenseDate->gt($period->end_date)) {
            throw new \Exception(
                "Tanggal beban berada di luar rentang periode aktif."
            );
        }

        $account = Account::findOrFail($data['account_id']);
        if ($account->is_header || !$account->is_active) {
            throw new InvalidAccountException("Akun '{$account->name}' tidak valid untuk pencatatan beban.");
        }

        return DB::transaction(function () use ($data) {
            $prefix = Setting::get('expense_prefix', 'EXP');
            $number = \App\Services\DocumentNumberService::generate('expenses', $prefix, $data['date']);

            $expense = Expense::create([
                'number' => $number,
                'period_id' => $data['period_id'],
                'date' => $data['date'],
                'name' => $data['name'],
                'account_id' => $data['account_id'],
                'wallet_id' => $data['wallet_id'],
                'contact_id' => $data['contact_id'] ?? null,
                'amount' => $data['amount'],
                'include_tax' => $data['include_tax'] ?? false,
                'receipt_number' => $data['receipt_number'] ?? null,
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'] ?? Auth::id(),
            ]);

            return $expense;
        });
    }

    public static function postExpense(Expense $expense): Journal
    {
        if ($expense->status !== 'draft') {
            throw new InvalidAccountException("Beban dengan status '{$expense->status}' tidak dapat diposting.");
        }

        $period = $expense->period;
        if ($period->is_closed) {
            throw new PeriodClosedException();
        }

        $expenseDate = Carbon::parse($expense->date);
        if ($expenseDate->lt($period->start_date) || $expenseDate->gt($period->end_date)) {
            throw new \Exception(
                "Tanggal beban berada di luar rentang periode aktif."
            );
        }

        $account = $expense->account;
        if ($account->is_header || !$account->is_active) {
            throw new InvalidAccountException("Akun '{$account->name}' tidak valid.");
        }

        $wallet = $expense->wallet;
        if (!$wallet || !$wallet->account_id) {
            throw new InvalidAccountException("Wallet untuk beban '{$expense->name}' tidak ditemukan atau tidak memiliki akun terkait.");
        }

        return DB::transaction(function () use ($expense, $period, $account, $wallet) {
            $journalLines = [];

            $amount = (float) $expense->amount;

            if ($expense->include_tax) {
                // Harga termasuk PPN: split menjadi DPP + PPN
                $ppnRule = TaxRule::where('code', 'PPN_11')
                    ->where('type', 'ppn')
                    ->where('is_active', true)
                    ->first();
                $ppnRate = $ppnRule ? (float) $ppnRule->rate / 100 : 0.11;
                $dpp = round($amount / (1 + $ppnRate), 2);
                $ppnAmount = $amount - $dpp;

                $ppnInputAccountId = Setting::where('key', 'ppn_input_account_id')->value('value');
                if (!$ppnInputAccountId) {
                    $ppnInputAccount = Account::where('code', '1500-00-030')->first();
                    $ppnInputAccountId = $ppnInputAccount?->id;
                }
                if (!$ppnInputAccountId) {
                    throw new InvalidAccountException('Akun PPN Masukan belum diatur. Setel di Pengaturan atau buat akun 1600.');
                }

                $journalLines[] = [
                    'account_id' => $account->id,
                    'debit_amount' => $dpp,
                    'credit_amount' => 0,
                    'description' => $expense->name . ' (DPP)',
                ];

                $journalLines[] = [
                    'account_id' => (int) $ppnInputAccountId,
                    'debit_amount' => $ppnAmount,
                    'credit_amount' => 0,
                    'description' => 'PPN Masukan: ' . $expense->number,
                ];
            } else {
                $journalLines[] = [
                    'account_id' => $account->id,
                    'debit_amount' => $amount,
                    'credit_amount' => 0,
                    'description' => $expense->name,
                ];
            }

            $journalLines[] = [
                'account_id' => $wallet->account_id,
                'debit_amount' => 0,
                'credit_amount' => $amount,
                'description' => "Pembayaran: {$expense->number}",
                'wallet_id' => $wallet->id,
            ];

            $journalData = [
                'date' => $expense->date,
                'period_id' => $period->id,
                'description' => "Beban: {$expense->name} ({$expense->number})",
                'source' => 'expense',
                'type' => 'normal',
                'ref_type' => 'expenses',
                'ref_id' => $expense->id,
                'created_by' => Auth::id(),
            ];

            $journal = JournalService::createJournal($journalData, $journalLines);

            $expense->update([
                'status' => 'posted',
                'journal_id' => $journal->id,
            ]);

            return $journal;
        });
    }

    public static function cancelExpense(Expense $expense): void
    {
        if ($expense->status === 'cancelled') {
            throw new InvalidAccountException('Beban sudah dibatalkan.');
        }

        DB::transaction(function () use ($expense) {
            if ($expense->journal_id) {
                $journal = Journal::find($expense->journal_id);
                if ($journal && $journal->type !== 'void') {
                    JournalService::voidJournal($journal, "Pembatalan beban {$expense->number}");
                }
            }

            $expense->update([
                'status' => 'cancelled',
            ]);
        });
    }
}
