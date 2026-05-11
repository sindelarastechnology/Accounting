<?php

namespace App\Services;

use App\Exceptions\AccountingImbalanceException;
use App\Exceptions\InvalidStateException;
use App\Exceptions\PeriodClosedException;
use App\Models\Journal;
use App\Models\JournalLine;
use App\Support\AccountingPrecision;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

abstract class BaseTransactionService
{
    /**
     * Validate that the period is open for the given date
     *
     * @param Carbon $date
     * @return void
     * @throws PeriodClosedException
     */
    protected function validatePeriodOpen(Carbon $date): void
    {
        $period = \App\Models\Period::where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->where('is_closed', false)
            ->first();

        if (!$period) {
            throw new PeriodClosedException('Periode untuk tanggal ' . $date->format('d/m/Y') . ' sudah ditutup atau tidak ditemukan.');
        }
    }

    /**
     * Validate that the model is not already posted
     *
     * @param Model $model
     * @return void
     * @throws InvalidStateException
     */
    protected function validateNotPosted(Model $model): void
    {
        if ($model->status === 'posted') {
            throw new InvalidStateException('Dokumen sudah diposting sebelumnya.');
        }
    }

    /**
     * Validate that the model is not cancelled
     *
     * @param Model $model
     * @return void
     * @throws InvalidStateException
     */
    protected function validateNotCancelled(Model $model): void
    {
        if ($model->status === 'cancelled') {
            throw new InvalidStateException('Dokumen sudah dibatalkan.');
        }
    }

    /**
     * Create journal entry with balanced validation
     *
     * @param array $data
     * @param array $lines
     * @return Journal
     * @throws AccountingImbalanceException
     */
    protected function createJournalEntry(array $data, array $lines): Journal
    {
        if (count($lines) < 2) {
            throw new \InvalidArgumentException('Jurnal minimal memiliki 2 baris.');
        }

        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($lines as $index => $line) {
            $debit = (float) ($line['debit_amount'] ?? 0);
            $credit = (float) ($line['credit_amount'] ?? 0);

            if ($debit > 0 && $credit > 0) {
                throw new \InvalidArgumentException(
                    'Baris jurnal ke-' . ($index + 1) . ' tidak boleh memiliki debit dan kredit sekaligus.'
                );
            }

            if ($debit === 0.0 && $credit === 0.0) {
                throw new \InvalidArgumentException(
                    'Baris jurnal ke-' . ($index + 1) . ' harus memiliki nilai debit atau kredit.'
                );
            }

            if ($debit < 0 || $credit < 0) {
                throw new \InvalidArgumentException(
                    'Nilai debit/kredit tidak boleh negatif pada baris ke-' . ($index + 1) . '.'
                );
            }

            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        if (AccountingPrecision::compare($totalDebit, $totalCredit) !== 0) {
            throw new AccountingImbalanceException();
        }

        return DB::transaction(function () use ($data, $lines) {
            $journal = JournalService::createJournal($data, $lines);
            return $journal;
        });
    }
}
