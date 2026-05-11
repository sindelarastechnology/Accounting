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

class CashBookPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationLabel = 'Buku Kas';

    protected static ?string $slug = 'reports/cash-book';

    protected static string $view = 'filament.pages.cash-book';

    public ?int $period_id = null;

    public ?string $date_from = null;

    public ?string $date_to = null;

    public ?array $wallet_ids = [];

    public ?string $category = 'all';

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
            Select::make('wallet_ids')
                ->label('Wallet')
                ->multiple()
                ->options(fn () => Wallet::active()->pluck('name', 'id'))
                ->live(),
            Select::make('category')
                ->label('Kategori')
                ->options([
                    'all' => 'Semua Transaksi',
                    'in' => 'Kas Masuk',
                    'out' => 'Kas Keluar',
                ])
                ->live(),
        ])->columns(5);
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

        $walletQuery = Wallet::active()->with('account');
        if (!empty($this->wallet_ids)) {
            $walletQuery->whereIn('id', $this->wallet_ids);
        }
        $wallets = $walletQuery->get();
        $walletAccountIds = $wallets->pluck('account_id')->filter()->values()->toArray();

        if (empty($walletAccountIds)) {
            return [
                'rows' => [],
                'total_masuk' => 0,
                'total_keluar' => 0,
                'opening_balance' => 0,
                'closing_balance' => 0,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ];
        }

        $dayBeforeStart = \Carbon\Carbon::parse($dateFrom)->subDay()->format('Y-m-d');

        // Hitung opening balance kombinasi semua wallet
        $openingBalance = 0;
        foreach ($walletAccountIds as $accId) {
            $bal = JournalService::getAccountBalanceUpToDate($accId, $dayBeforeStart);
            $openingBalance += $bal['balance'];
        }

        // Map wallet account_id => wallet name
        $walletMap = [];
        foreach ($wallets as $w) {
            if ($w->account) {
                $walletMap[$w->account->id] = $w->name;
            }
        }

        $query = DB::table('journal_lines')
            ->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
            ->whereIn('journal_lines.account_id', $walletAccountIds)
            ->where('journals.type', '!=', 'void')
            ->where('journals.date', '>=', $dateFrom)
            ->where('journals.date', '<=', $dateTo);

        if ($this->category === 'in') {
            $query->where('journal_lines.debit_amount', '>', 0);
        } elseif ($this->category === 'out') {
            $query->where('journal_lines.credit_amount', '>', 0);
        }

        $lines = $query->selectRaw('
                journal_lines.id as line_id,
                journal_lines.account_id,
                journal_lines.debit_amount,
                journal_lines.credit_amount,
                journal_lines.journal_id,
                journals.date,
                journals.number as journal_number,
                journals.description
            ')
            ->orderBy('journals.date')
            ->orderBy('journals.id')
            ->get();

        $rows = [];
        $runningBalance = $openingBalance;
        $totalMasuk = 0;
        $totalKeluar = 0;

        // Cache journal counter-lines
        $counterCache = [];

        foreach ($lines as $line) {
            $masuk = (float) $line->debit_amount;
            $keluar = (float) $line->credit_amount;
            $runningBalance += $masuk - $keluar;
            $totalMasuk += $masuk;
            $totalKeluar += $keluar;

            // Determine counter account
            $journalId = $line->journal_id;
            if (!isset($counterCache[$journalId])) {
                $counterLines = DB::table('journal_lines')
                    ->join('accounts', 'journal_lines.account_id', '=', 'accounts.id')
                    ->where('journal_lines.journal_id', $journalId)
                    ->whereNotIn('journal_lines.account_id', $walletAccountIds)
                    ->selectRaw('accounts.name')
                    ->distinct()
                    ->pluck('name');

                if ($counterLines->count() === 1) {
                    $counterCache[$journalId] = $counterLines->first();
                } elseif ($counterLines->count() > 1) {
                    $counterCache[$journalId] = 'Berbagai Akun';
                } else {
                    $counterCache[$journalId] = '-';
                }
            }

            $rows[] = [
                'date' => $line->date,
                'journal_number' => $line->journal_number,
                'description' => $line->description,
                'counter_account' => $counterCache[$journalId],
                'wallet_name' => $walletMap[$line->account_id] ?? '-',
                'masuk' => $masuk,
                'keluar' => $keluar,
                'saldo' => $runningBalance,
            ];
        }

        return [
            'rows' => $rows,
            'total_masuk' => $totalMasuk,
            'total_keluar' => $totalKeluar,
            'opening_balance' => $openingBalance,
            'closing_balance' => $openingBalance + $totalMasuk - $totalKeluar,
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
            (new \App\Exports\CashBookExport($this->wallet_ids, $this->category, $this->date_from, $this->date_to, $this->period_id))->download('Buku_Kas.xlsx')->getFile()
        );
    }

    public function exportPdf()
    {
        $data = $this->getData();
        $period = $this->period_id ? Period::find($this->period_id) : null;

        $pdf = Pdf::loadView('pdf.cash_book', [
            'data' => $data,
            'period' => $period,
            'generated_at' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'Buku_Kas.pdf');
    }

    public function printReport()
    {
        $this->dispatch('print-report');
    }
}
