<?php

namespace App\Filament\Pages;

use App\Models\Period;
use App\Services\JournalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;

class Pph23ReportPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 9;

    protected static ?string $navigationLabel = 'Laporan PPh 23';

    protected static string $view = 'filament.pages.pph23-report';

    public ?int $period_id = null;

    public ?string $date_from = null;

    public ?string $date_to = null;

    public function mount(): void
    {
        $this->period_id = Period::where('is_closed', false)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->value('id')
            ?? Period::value('id');
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('period_id')
                ->label('Periode')
                ->options(fn () => Period::orderBy('start_date', 'desc')->pluck('name', 'id'))
                ->searchable()
                ->live(),
            DatePicker::make('date_from')
                ->label('Dari Tanggal')
                ->live(),
            DatePicker::make('date_to')
                ->label('Sampai Tanggal')
                ->live(),
        ])->columns(3);
    }

    public function getData(): array
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

        return JournalService::getPph23Report($this->period_id, $dateFrom, $dateTo);
    }

    public function exportPdf()
    {
        $data = $this->getData();
        $period = $this->period_id ? Period::find($this->period_id) : null;

        $pdf = Pdf::loadView('pdf.pph23_report', [
            'data' => $data,
            'period' => $period,
            'generated_at' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'Laporan_PPh23.pdf');
    }
}
