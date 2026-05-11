<?php

namespace App\Filament\Pages;

use App\Models\Period;
use App\Models\Wallet;
use App\Services\JournalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class WalletMutationPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 7;

    protected static ?string $navigationLabel = 'Mutasi Kas & Bank';

    protected static ?string $slug = 'reports/wallet-mutation';

    protected static string $view = 'filament.pages.wallet-mutation';

    public ?int $period_id = null;

    public ?string $date_from = null;

    public ?string $date_to = null;

    public ?int $wallet_id = null;

    public function mount(): void
    {
        $this->period_id = Period::where('is_closed', false)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->value('id')
            ?? Period::value('id');

        if (request()->filled('date_from')) {
            $this->date_from = request('date_from');
        }
        if (request()->filled('date_to')) {
            $this->date_to = request('date_to');
        }
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
            Select::make('wallet_id')
                ->label('Wallet')
                ->options(fn () => ['' => 'Semua Wallet'] + Wallet::active()->pluck('name', 'id')->toArray())
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

        if (!$dateFrom) {
            $dateFrom = now()->startOfMonth()->format('Y-m-d');
        }
        if (!$dateTo) {
            $dateTo = now()->format('Y-m-d');
        }

        $wallets = Wallet::active()->with('account');
        if ($this->wallet_id) {
            $wallets->where('id', $this->wallet_id);
        }
        $wallets = $wallets->get();

        $result = [];
        $dayBeforeStart = \Carbon\Carbon::parse($dateFrom)->subDay()->format('Y-m-d');

        foreach ($wallets as $wallet) {
            if (!$wallet->account) continue;

            $accountId = $wallet->account->id;

            $openingBal = JournalService::getAccountBalanceUpToDate($accountId, $dayBeforeStart);
            $openingBalance = $openingBal['balance'];

            $lines = DB::table('journal_lines')
                ->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
                ->where('journal_lines.account_id', $accountId)
                ->where('journals.type', '!=', 'void')
                ->where('journals.date', '>=', $dateFrom)
                ->where('journals.date', '<=', $dateTo)
                ->selectRaw('
                    journals.date,
                    journals.number as journal_number,
                    journals.description,
                    journal_lines.debit_amount,
                    journal_lines.credit_amount
                ')
                ->orderBy('journals.date')
                ->orderBy('journals.id')
                ->get();

            $rows = [];
            $runningBalance = $openingBalance;
            $totalMasuk = 0;
            $totalKeluar = 0;

            foreach ($lines as $line) {
                $masuk = (float) $line->debit_amount;
                $keluar = (float) $line->credit_amount;
                $runningBalance += $masuk - $keluar;
                $totalMasuk += $masuk;
                $totalKeluar += $keluar;

                $rows[] = [
                    'date' => $line->date,
                    'description' => $line->description,
                    'journal_number' => $line->journal_number,
                    'masuk' => $masuk,
                    'keluar' => $keluar,
                    'saldo' => $runningBalance,
                ];
            }

            $result[] = [
                'wallet' => $wallet,
                'opening_balance' => $openingBalance,
                'rows' => $rows,
                'total_masuk' => $totalMasuk,
                'total_keluar' => $totalKeluar,
                'closing_balance' => $openingBalance + $totalMasuk - $totalKeluar,
            ];
        }

        return [
            'wallets' => $result,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
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
            (new \App\Exports\WalletMutationExport($this->wallet_id, $this->date_from, $this->date_to, $this->period_id))->download('Mutasi_Kas.xlsx')->getFile()
        );
    }

    public function exportPdf()
    {
        $data = $this->getData();
        $period = $this->period_id ? Period::find($this->period_id) : null;

        $pdf = Pdf::loadView('pdf.wallet_mutation', [
            'data' => $data,
            'period' => $period,
            'generated_at' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'Mutasi_Kas.pdf');
    }

    public function printReport()
    {
        $this->dispatch('print-report');
    }
}
