<?php

namespace App\Exports;

use App\Services\JournalService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BalanceSheetExport implements FromArray, WithHeadings, WithStyles
{
    protected ?int $period_id;
    protected ?string $as_of_date;

    public function __construct(?int $period_id = null, ?string $as_of_date = null)
    {
        $this->period_id = $period_id;
        $this->as_of_date = $as_of_date;
    }

    public function array(): array
    {
        $data = JournalService::getBalanceSheet($this->period_id, $this->as_of_date);
        $rows = [];

        $rows[] = ['ASET'];
        $rows[] = ['Aset Lancar'];
        foreach ($data['assets']['current'] as $item) {
            $rows[] = [$item['account']->code . ' - ' . $item['account']->name, $item['balance']];
        }
        $rows[] = ['Total Aset Lancar', $data['assets']['total_current']];
        $rows[] = [''];
        $rows[] = ['Aset Tetap'];
        foreach ($data['assets']['fixed'] as $item) {
            $rows[] = [$item['account']->code . ' - ' . $item['account']->name, $item['balance']];
        }
        $rows[] = ['Total Aset Tetap', $data['assets']['total_fixed']];
        $rows[] = ['TOTAL ASET', $data['assets']['total']];
        $rows[] = [''];

        $rows[] = ['LIABILITAS & EKUITAS'];
        $rows[] = ['Liabilitas Jangka Pendek'];
        foreach ($data['liabilities']['current'] as $item) {
            $rows[] = [$item['account']->code . ' - ' . $item['account']->name, $item['balance']];
        }
        $rows[] = ['Total Liabilitas Jangka Pendek', $data['liabilities']['total_current']];
        $rows[] = [''];
        $rows[] = ['Ekuitas'];
        foreach ($data['equity']['accounts'] as $item) {
            $rows[] = [$item['account']->code . ' - ' . $item['account']->name, $item['balance']];
        }
        $rows[] = ['Laba Periode Ini', $data['equity']['net_income']];
        $rows[] = ['Total Ekuitas', $data['equity']['total']];
        $rows[] = ['TOTAL LIABILITAS + EKUITAS', $data['liabilities']['total'] + $data['equity']['total']];

        return $rows;
    }

    public function headings(): array
    {
        return ['Akun / Keterangan', 'Jumlah (Rp)'];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
