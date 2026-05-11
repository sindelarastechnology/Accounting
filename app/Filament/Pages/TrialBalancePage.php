<?php

namespace App\Filament\Pages;

use App\Models\Account;
use App\Models\Period;
use App\Services\JournalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\HtmlString;

class TrialBalancePage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Neraca Saldo';

    protected static string $view = 'filament.pages.trial-balance';

    public ?int $period_id = null;

    public bool $hide_zero = true;

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
            Toggle::make('hide_zero')
                ->label('Sembunyikan akun bersaldo nol')
                ->default(true)
                ->live(),
        ])->columns(2);
    }

    public function getData(): array
    {
        $trialBalance = JournalService::getTrialBalance($this->period_id);

        if ($this->hide_zero) {
            $trialBalance = $trialBalance->filter(fn ($item) => abs($item['debit']) > 0.0001 || abs($item['credit']) > 0.0001);
        }

        $headers = Account::where('is_header', true)
            ->orderBy('code')
            ->get()
            ->keyBy('id');

        $rows = [];
        foreach ($trialBalance as $item) {
            $account = $item['account'];
            if ($account->parent_id && isset($headers[$account->parent_id])) {
                $header = $headers[$account->parent_id];
                if (!isset($rows[$header->id])) {
                    $rows[$header->id] = ['header' => $header, 'items' => []];
                }
                $rows[$header->id]['items'][] = $item;
            } else {
                if (!isset($rows['ungrouped'])) {
                    $rows['ungrouped'] = ['header' => null, 'items' => []];
                }
                $rows['ungrouped']['items'][] = $item;
            }
        }

        $totalDebit = $trialBalance->sum('debit');
        $totalCredit = $trialBalance->sum('credit');
        $isBalanced = abs($totalDebit - $totalCredit) < 0.01;

        return [
            'rows' => $rows,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'is_balanced' => $isBalanced,
            'difference' => abs($totalDebit - $totalCredit),
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
            (new \App\Exports\TrialBalanceExport($this->period_id, $this->hide_zero))->download('Neraca_Saldo.xlsx')->getFile()
        );
    }

    public function exportPdf()
    {
        $data = $this->getData();
        $period = $this->period_id ? Period::find($this->period_id) : null;

        $pdf = Pdf::loadView('pdf.trial_balance', [
            'data' => $data,
            'period' => $period,
            'generated_at' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'portrait');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'Neraca_Saldo.pdf');
    }

    public function printReport()
    {
        $this->dispatch('print-report');
    }
}
