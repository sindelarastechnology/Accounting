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

class BalanceSheetPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Neraca';

    protected static string $view = 'filament.pages.balance-sheet';

    public ?string $as_of_date = null;

    public ?int $period_id = null;

    public function mount(): void
    {
        $this->as_of_date = now()->format('Y-m-d');
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            DatePicker::make('as_of_date')
                ->label('Per Tanggal')
                ->default(now())
                ->required()
                ->live(),
            Select::make('period_id')
                ->label('Periode (opsional)')
                ->options(fn () => Period::orderBy('start_date', 'desc')->pluck('name', 'id'))
                ->searchable()
                ->nullable()
                ->live(),
        ])->columns(2);
    }

    public function getData(): array
    {
        return JournalService::getBalanceSheet($this->period_id, $this->as_of_date);
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
            (new \App\Exports\BalanceSheetExport($this->period_id, $this->as_of_date))->download('Neraca.xlsx')->getFile()
        );
    }

    public function exportPdf()
    {
        $data = $this->getData();
        $period = $this->period_id ? Period::find($this->period_id) : null;

        $pdf = Pdf::loadView('pdf.balance_sheet', [
            'data' => $data,
            'period' => $period,
            'generated_at' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'portrait');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'Neraca.pdf');
    }

    public function printReport()
    {
        $this->dispatch('print-report');
    }
}
