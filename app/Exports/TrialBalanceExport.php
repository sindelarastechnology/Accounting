<?php

namespace App\Exports;

use App\Services\JournalService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TrialBalanceExport implements FromArray, WithHeadings, WithStyles
{
    protected ?int $period_id;
    protected bool $hide_zero;

    public function __construct(?int $period_id = null, bool $hide_zero = true)
    {
        $this->period_id = $period_id;
        $this->hide_zero = $hide_zero;
    }

    public function array(): array
    {
        $trialBalance = JournalService::getTrialBalance($this->period_id);

        if ($this->hide_zero) {
            $trialBalance = $trialBalance->filter(fn ($item) => abs($item['debit']) > 0.0001 || abs($item['credit']) > 0.0001);
        }

        $rows = [];
        foreach ($trialBalance as $item) {
            $rows[] = [
                $item['account']->code,
                $item['account']->name,
                $item['debit'],
                $item['credit'],
            ];
        }

        $totalDebit = $trialBalance->sum('debit');
        $totalCredit = $trialBalance->sum('credit');
        $rows[] = ['', 'TOTAL', $totalDebit, $totalCredit];

        return $rows;
    }

    public function headings(): array
    {
        return ['Kode Akun', 'Nama Akun', 'Debit (Rp)', 'Kredit (Rp)'];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $sheet->getHighestRow();
        return [
            1 => ['font' => ['bold' => true]],
            $lastRow => ['font' => ['bold' => true]],
        ];
    }
}
