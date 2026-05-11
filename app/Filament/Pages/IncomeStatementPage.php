<?php

namespace App\Filament\Pages;

use App\Models\Period;
use App\Services\JournalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;

class IncomeStatementPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Laba Rugi';

    protected static string $view = 'filament.pages.income-statement';

    public ?int $period_id = null;

    public ?string $date_from = null;

    public ?string $date_to = null;

    public ?int $comparison_period_id = null;

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
            Select::make('comparison_period_id')
                ->label('Periode Perbandingan')
                ->options(fn () => Period::orderBy('start_date', 'desc')->pluck('name', 'id'))
                ->searchable()
                ->nullable()
                ->live(),
        ])->columns(4);
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

        $data = JournalService::getIncomeStatement($this->period_id, $dateFrom, $dateTo);

        $comparison = null;
        if ($this->comparison_period_id) {
            $comparison = JournalService::getIncomeStatement($this->comparison_period_id);
        }

        return [
            'current' => $data,
            'comparison' => $comparison,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn () => $this->exportExcel()),
            Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action(fn () => $this->exportPdf()),
            Action::make('printReport')
                ->label('Print')
                ->icon('heroicon-o-printer')
                ->color('warning')
                ->action(fn () => $this->printReport()),
        ];
    }

    public function exportExcel()
    {
        return response()->download(
            (new \App\Exports\IncomeStatementExport($this->period_id, $this->date_from, $this->date_to))->download('Laba_Rugi.xlsx')->getFile()
        );
    }

    public function exportPdf()
    {
        $data = $this->getData();
        $period = $this->period_id ? Period::find($this->period_id) : null;

        $pdf = Pdf::loadView('pdf.income_statement', [
            'data' => $data,
            'period' => $period,
            'generated_at' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'portrait');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'Laba_Rugi.pdf');
    }

    public function printReport()
    {
        $this->dispatch('print-report');
    }
}
