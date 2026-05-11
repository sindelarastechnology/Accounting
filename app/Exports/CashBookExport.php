<?php

namespace App\Exports;

use App\Models\Period;
use App\Models\Wallet;
use App\Services\JournalService;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CashBookExport implements FromArray, WithHeadings
{
    protected ?array $wallet_ids;
    protected ?string $category;
    protected ?string $date_from;
    protected ?string $date_to;
    protected ?int $period_id;

    public function __construct(?array $wallet_ids = null, ?string $category = 'all', ?string $date_from = null, ?string $date_to = null, ?int $period_id = null)
    {
        $this->wallet_ids = $wallet_ids;
        $this->category = $category;
        $this->date_from = $date_from;
        $this->date_to = $date_to;
        $this->period_id = $period_id;
    }

    public function headings(): array
    {
        return ['Tanggal', 'No. Jurnal', 'Keterangan', 'Akun Lawan', 'Wallet', 'Masuk (Rp)', 'Keluar (Rp)', 'Saldo (Rp)'];
    }

    public function array(): array
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

        $walletQuery = Wallet::active()->with('account');
        if (!empty($this->wallet_ids)) {
            $walletQuery->whereIn('id', $this->wallet_ids);
        }
        $wallets = $walletQuery->get();
        $walletAccountIds = $wallets->pluck('account_id')->filter()->values()->toArray();

        if (empty($walletAccountIds)) {
            return [['Tidak ada data']];
        }

        $dayBeforeStart = \Carbon\Carbon::parse($dateFrom)->subDay()->format('Y-m-d');
        $openingBalance = 0;
        foreach ($walletAccountIds as $accId) {
            $bal = JournalService::getAccountBalanceUpToDate($accId, $dayBeforeStart);
            $openingBalance += $bal['balance'];
        }

        $walletMap = [];
        foreach ($wallets as $w) {
            if ($w->account) $walletMap[$w->account->id] = $w->name;
        }

        $query = DB::table('journal_lines')
            ->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
            ->whereIn('journal_lines.account_id', $walletAccountIds)
            ->where('journals.type', '!=', 'void')
            ->where('journals.date', '>=', $dateFrom)
            ->where('journals.date', '<=', $dateTo);

        if ($this->category === 'in') {
            $query->where('journal_lines.debit_amount', '>', 0);
        } elseif ($this->category === 'out') {
            $query->where('journal_lines.credit_amount', '>', 0);
        }

        $lines = $query->selectRaw('
                journal_lines.account_id,
                journal_lines.debit_amount,
                journal_lines.credit_amount,
                journal_lines.journal_id,
                journals.date,
                journals.number as journal_number,
                journals.description
            ')
            ->orderBy('journals.date')
            ->orderBy('journals.id')
            ->get();

        $rows = [];
        $runningBalance = $openingBalance;
        $counterCache = [];

        foreach ($lines as $line) {
            $masuk = (float) $line->debit_amount;
            $keluar = (float) $line->credit_amount;
            $runningBalance += $masuk - $keluar;

            $journalId = $line->journal_id;
            if (!isset($counterCache[$journalId])) {
                $counterLines = DB::table('journal_lines')
                    ->join('accounts', 'journal_lines.account_id', '=', 'accounts.id')
                    ->where('journal_lines.journal_id', $journalId)
                    ->whereNotIn('journal_lines.account_id', $walletAccountIds)
                    ->selectRaw('accounts.name')
                    ->distinct()
                    ->pluck('name');

                $counterCache[$journalId] = $counterLines->count() === 1
                    ? $counterLines->first()
                    : ($counterLines->count() > 1 ? 'Berbagai Akun' : '-');
            }

            $rows[] = [
                $line->date,
                $line->journal_number,
                $line->description,
                $counterCache[$journalId],
                $walletMap[$line->account_id] ?? '-',
                $masuk > 0 ? round($masuk, 2) : '',
                $keluar > 0 ? round($keluar, 2) : '',
                round($runningBalance, 2),
            ];
        }

        return $rows;
    }
}
