<?php

namespace App\Exports;

use App\Services\JournalService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CashFlowExport implements FromArray, WithHeadings, WithStyles
{
    protected ?int $period_id;
    protected ?string $date_from;
    protected ?string $date_to;

    public function __construct(?int $period_id = null, ?string $date_from = null, ?string $date_to = null)
    {
        $this->period_id = $period_id;
        $this->date_from = $date_from;
        $this->date_to = $date_to;
    }

    public function array(): array
    {
        $data = JournalService::getCashFlow($this->period_id, $this->date_from, $this->date_to);
        $rows = [];

        $rows[] = ['AKTIVITAS OPERASI'];
        $rows[] = ['Laba Bersih', $data['operating']['net_income']];
        $rows[] = ['Penyesuaian:'];
        foreach ($data['operating']['adjustments'] as $adj) {
            $rows[] = ['  ' . $adj['label'], $adj['amount']];
        }
        $rows[] = ['Kas dari Aktivitas Operasi', $data['operating']['total']];
        $rows[] = [''];

        $rows[] = ['AKTIVITAS INVESTASI'];
        foreach ($data['investing']['items'] as $item) {
            $rows[] = [$item['label'], $item['amount']];
        }
        $rows[] = ['Kas dari Aktivitas Investasi', $data['investing']['total']];
        $rows[] = [''];

        $rows[] = ['AKTIVITAS PENDANAAN'];
        foreach ($data['financing']['items'] as $item) {
            $rows[] = [$item['label'], $item['amount']];
        }
        $rows[] = ['Kas dari Aktivitas Pendanaan', $data['financing']['total']];
        $rows[] = [''];

        $rows[] = ['Kenaikan/(Penurunan) Kas Bersih', $data['net_change']];
        $rows[] = ['Saldo Kas Awal Periode', $data['opening_cash']];
        $rows[] = ['SALDO KAS AKHIR PERIODE', $data['closing_cash']];

        return $rows;
    }

    public function headings(): array
    {
        return ['Keterangan', 'Jumlah (Rp)'];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
