<?php

namespace App\Exports;

use App\Services\JournalService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AgingReportExport implements FromArray, WithHeadings, WithStyles
{
    protected string $type;
    protected ?string $as_of_date;
    protected ?int $contact_id;

    public function __construct(string $type = 'receivable', ?string $as_of_date = null, ?int $contact_id = null)
    {
        $this->type = $type;
        $this->as_of_date = $as_of_date;
        $this->contact_id = $contact_id;
    }

    public function array(): array
    {
        $data = JournalService::getAgingReport($this->type, $this->as_of_date);

        if ($this->contact_id) {
            $data['items'] = array_filter($data['items'], fn ($item) => $item['contact']->id == $this->contact_id);
            $data['items'] = array_values($data['items']);
        }

        $rows = [];
        foreach ($data['items'] as $item) {
            $rows[] = [
                $item['contact']->name,
                $item['current'],
                $item['days_1_30'],
                $item['days_31_60'],
                $item['days_61_90'],
                $item['over_90'],
                $item['total'],
            ];
        }

        $rows[] = [
            'TOTAL',
            $data['totals']['current'],
            $data['totals']['days_1_30'],
            $data['totals']['days_31_60'],
            $data['totals']['days_61_90'],
            $data['totals']['over_90'],
            $data['grand_total'],
        ];

        return $rows;
    }

    public function headings(): array
    {
        return ['Kontak', 'Belum Jatuh Tempo', '1-30 Hari', '31-60 Hari', '61-90 Hari', '>90 Hari', 'Total'];
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
