<?php

namespace App\Filament\Pages;

use App\Models\Contact;
use App\Services\JournalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;

class AgingReportPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Aging Piutang/Hutang';

    protected static string $view = 'filament.pages.aging-report';

    public string $type = 'receivable';

    public ?string $as_of_date = null;

    public ?int $contact_id = null;

    public function mount(): void
    {
        $this->as_of_date = now()->format('Y-m-d');

        // Allow direct link via ?type=receivable or ?type=payable
        if (in_array(request('type'), ['receivable', 'payable'])) {
            $this->type = request('type');
        }
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Radio::make('type')
                ->label('Tipe')
                ->options([
                    'receivable' => 'Piutang Usaha',
                    'payable' => 'Hutang Usaha',
                ])
                ->default('receivable')
                ->inline()
                ->live(),
            DatePicker::make('as_of_date')
                ->label('Per Tanggal')
                ->default(now())
                ->required()
                ->live(),
            Select::make('contact_id')
                ->label('Filter Kontak (opsional)')
                ->options(fn () => Contact::orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->nullable()
                ->live(),
        ])->columns(3);
    }

    public function getData(): array
    {
        $data = JournalService::getAgingReport($this->type, $this->as_of_date);

        if ($this->contact_id) {
            $data['items'] = array_filter($data['items'], fn ($item) => $item['contact']->id == $this->contact_id);
            $data['items'] = array_values($data['items']);
            $data['totals'] = [
                'current' => collect($data['items'])->sum('current'),
                'days_1_30' => collect($data['items'])->sum('days_1_30'),
                'days_31_60' => collect($data['items'])->sum('days_31_60'),
                'days_61_90' => collect($data['items'])->sum('days_61_90'),
                'over_90' => collect($data['items'])->sum('over_90'),
            ];
            $data['totals']['total'] = array_sum($data['totals']);
            $data['grand_total'] = collect($data['items'])->sum('total');
        }

        return $data;
    }

    public function exportExcel()
    {
        return response()->download(
            (new \App\Exports\AgingReportExport($this->type, $this->as_of_date, $this->contact_id))->download('Aging_Report.xlsx')->getFile()
        );
    }

    public function exportPdf()
    {
        $data = $this->getData();

        $pdf = Pdf::loadView('pdf.aging_report', [
            'data' => $data,
            'generated_at' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'portrait');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'Aging_Report.pdf');
    }
}
