<?php

namespace App\Exports;

use App\Services\JournalService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class IncomeStatementExport implements FromArray, WithHeadings, WithStyles
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
        $data = JournalService::getIncomeStatement($this->period_id, $this->date_from, $this->date_to);
        $rows = [];

        $rows[] = ['PENDAPATAN'];
        if (count($data['revenue_operating']) > 0) {
            $rows[] = ['Pendapatan Usaha'];
            foreach ($data['revenue_operating'] as $item) {
                $rows[] = [$item['account']->code . ' - ' . $item['account']->name, $item['balance']];
            }
        }
        if (count($data['revenue_other']) > 0) {
            $rows[] = ['Pendapatan Lain-lain'];
            foreach ($data['revenue_other'] as $item) {
                $rows[] = [$item['account']->code . ' - ' . $item['account']->name, $item['balance']];
            }
        }
        $rows[] = ['Total Pendapatan', $data['total_revenue']];
        $rows[] = [''];

        $rows[] = ['HARGA POKOK PENJUALAN'];
        foreach ($data['cogs'] as $item) {
            $rows[] = [$item['account']->code . ' - ' . $item['account']->name, $item['balance']];
        }
        $rows[] = ['Total HPP', $data['total_cogs']];
        $rows[] = ['LABA KOTOR', $data['gross_profit']];
        $rows[] = [''];

        $rows[] = ['BEBAN OPERASIONAL'];
        foreach ($data['expense_operating'] as $item) {
            $rows[] = [$item['account']->code . ' - ' . $item['account']->name, $item['balance']];
        }
        $rows[] = ['Total Beban Operasional', $data['total_expense_operating']];
        $rows[] = ['LABA USAHA', $data['operating_profit']];
        $rows[] = [''];

        if (count($data['revenue_other']) > 0 || count($data['expense_other']) > 0) {
            $rows[] = ['BEBAN / PENDAPATAN LAIN-LAIN'];
            if (count($data['revenue_other']) > 0) {
                $rows[] = ['Pendapatan Lain-lain'];
                foreach ($data['revenue_other'] as $item) {
                    $rows[] = [$item['account']->code . ' - ' . $item['account']->name, $item['balance']];
                }
            }
            if (count($data['expense_other']) > 0) {
                $rows[] = ['Beban Lain-lain'];
                foreach ($data['expense_other'] as $item) {
                    $rows[] = [$item['account']->code . ' - ' . $item['account']->name, $item['balance']];
                }
            }
            $rows[] = ['Total Lain-lain', $data['other_total']];
            $rows[] = [''];
        }

        if ($data['tax_expense'] > 0) {
            $rows[] = ['Beban Pajak Penghasilan', $data['tax_expense']];
        }

        $rows[] = ['LABA / RUGI BERSIH', $data['net_income']];

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
