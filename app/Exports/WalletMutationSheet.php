<?php

namespace App\Exports;

use App\Models\Wallet;
use App\Services\JournalService;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class WalletMutationSheet implements FromArray, WithHeadings, WithTitle
{
    protected ?Wallet $wallet;
    protected string $date_from;
    protected string $date_to;

    public function __construct(?Wallet $wallet, string $date_from, string $date_to)
    {
        $this->wallet = $wallet;
        $this->date_from = $date_from;
        $this->date_to = $date_to;
    }

    public function title(): string
    {
        return $this->wallet ? $this->wallet->name : 'Kosong';
    }

    public function headings(): array
    {
        return ['Tanggal', 'No. Jurnal', 'Keterangan', 'Masuk (Rp)', 'Keluar (Rp)', 'Saldo (Rp)'];
    }

    public function array(): array
    {
        if (!$this->wallet || !$this->wallet->account) {
            return [['Tidak ada data']];
        }

        $accountId = $this->wallet->account->id;
        $dayBeforeStart = \Carbon\Carbon::parse($this->date_from)->subDay()->format('Y-m-d');
        $openingBal = JournalService::getAccountBalanceUpToDate($accountId, $dayBeforeStart);
        $openingBalance = $openingBal['balance'];

        $lines = DB::table('journal_lines')
            ->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
            ->where('journal_lines.account_id', $accountId)
            ->where('journals.type', '!=', 'void')
            ->where('journals.date', '>=', $this->date_from)
            ->where('journals.date', '<=', $this->date_to)
            ->selectRaw('
                journals.date,
                journals.number as journal_number,
                journals.description,
                journal_lines.debit_amount,
                journal_lines.credit_amount
            ')
            ->orderBy('journals.date')
            ->orderBy('journals.id')
            ->get();

        $rows = [];
        $runningBalance = $openingBalance;

        if ($openingBalance != 0) {
            $rows[] = [$this->date_from, '', 'Saldo Awal', round($openingBalance, 2), '', round($runningBalance, 2)];
        }

        foreach ($lines as $line) {
            $masuk = (float) $line->debit_amount;
            $keluar = (float) $line->credit_amount;
            $runningBalance += $masuk - $keluar;
            $rows[] = [
                $line->date,
                $line->journal_number,
                $line->description,
                $masuk > 0 ? round($masuk, 2) : '',
                $keluar > 0 ? round($keluar, 2) : '',
                round($runningBalance, 2),
            ];
        }

        return $rows;
    }
}
