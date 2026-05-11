<?php

namespace App\Filament\Pages;

use App\Models\Period;
use App\Models\TaxPayment;
use App\Services\JournalService;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;

class TaxDashboardPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Dashboard Pajak';

    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.tax-dashboard';

    public ?int $period_id = null;

    public function mount(): void
    {
        $this->period_id = Period::where('is_closed', false)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->value('id')
            ?? Period::orderBy('start_date', 'desc')->value('id');
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('period_id')
                ->label('Periode')
                ->options(fn () => Period::orderBy('start_date', 'desc')->pluck('name', 'id'))
                ->searchable()
                ->live(),
        ]);
    }

    public function getData(): array
    {
        if (!$this->period_id) {
            return [];
        }

        $period = Period::find($this->period_id);
        if (!$period) {
            return [];
        }

        $dateFrom = $period->start_date->format('Y-m-d');
        $dateTo = $period->end_date->format('Y-m-d');

        $ppn = JournalService::getPpnReport($this->period_id, $dateFrom, $dateTo);
        $pph23 = JournalService::getPph23Report($this->period_id, $dateFrom, $dateTo);
        $pph21 = JournalService::getPph21Report($this->period_id, $dateFrom, $dateTo);
        $pph4a2 = JournalService::getPph4a2Report($this->period_id, $dateFrom, $dateTo);

        $allPayments = TaxPayment::where('status', 'posted')
            ->where('period_id', $this->period_id)
            ->orderBy('payment_date')
            ->get();

        return [
            'period' => $period,
            'ppn' => $ppn,
            'pph23' => $pph23,
            'pph21' => $pph21,
            'pph4a2' => $pph4a2,
            'all_payments' => $allPayments,
        ];
    }

    public static function taxTypeLabel(string $type): string
    {
        return TaxPayment::TAX_TYPES[$type] ?? $type;
    }
}
