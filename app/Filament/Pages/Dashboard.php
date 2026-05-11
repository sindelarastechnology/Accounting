<?php

namespace App\Filament\Pages;

use App\Models\Invoice;
use App\Models\Journal;
use App\Models\Period;
use App\Models\Purchase;
use App\Models\Wallet;
use App\Services\JournalService;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class Dashboard extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.pages.dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = -2;

    public string $selectedPeriod = '';

    public array $dashboardData = [];

    public function mount(): void
    {
        $period = Period::where('is_closed', false)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first() ?? Period::where('is_closed', false)->latest()->first();

        $this->selectedPeriod = (string) ($period?->id ?? '');
        $this->loadData();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('selectedPeriod')
                ->options(
                    Period::orderByDesc('start_date')
                        ->get()
                        ->mapWithKeys(fn ($p) => [
                            $p->id => $p->name . ' (' . $p->start_date->format('M Y') . ')',
                        ])
                )
                ->live()
                ->afterStateUpdated(fn () => $this->loadData())
                ->placeholder('Pilih periode')
                ->extraAttributes(['style' => 'font-size:0.875rem']),
        ])->statePath('');
    }

    protected function loadData(): void
    {
        $period = Period::find($this->selectedPeriod);
        if (!$period) {
            $this->dashboardData = [];
            return;
        }

        $dateFrom = $period->start_date->format('Y-m-d');
        $dateTo = $period->end_date->format('Y-m-d');

        $incomeData = JournalService::getIncomeStatement($period->id);
        $revenue = (float) ($incomeData['total_revenue'] ?? 0);
        $expense = (float) ($incomeData['total_expenses'] ?? 0) + (float) ($incomeData['total_cogs'] ?? 0);
        $netProfit = $revenue - $expense;

        $wallets = Wallet::active()->with('account')->get();
        $walletBalances = [];
        $totalCash = 0;
        foreach ($wallets as $wallet) {
            if (!$wallet->account) continue;
            $bal = JournalService::getAccountBalanceUpToDate($wallet->account_id, $dateTo);
            $balance = (float) ($bal['balance'] ?? 0);
            $totalCash += $balance;
            $walletBalances[] = [
                'name' => $wallet->name,
                'type' => $wallet->type,
                'balance' => $balance,
            ];
        }

        $totalAR = (float) Invoice::whereIn('status', ['posted', 'partially_paid'])
            ->sum('due_amount');
        $totalAP = (float) Purchase::whereIn('status', ['posted', 'partially_paid'])
            ->sum('due_amount');

        $overdueAR = (float) Invoice::whereIn('status', ['posted', 'partially_paid'])
            ->where('due_date', '<', now()->subDays(30))
            ->sum('due_amount');
        $overdueAP = (float) Purchase::whereIn('status', ['posted', 'partially_paid'])
            ->where('due_date', '<', now()->subDays(30))
            ->sum('due_amount');

        $arTotalCurrent = (float) Invoice::whereIn('status', ['posted', 'partially_paid'])
            ->where('due_date', '>=', now())
            ->sum('due_amount');
        $arTotal30 = (float) Invoice::whereIn('status', ['posted', 'partially_paid'])
            ->whereBetween('due_date', [now()->subDays(60), now()->subDays(30)])
            ->sum('due_amount') + (float) Invoice::whereIn('status', ['posted', 'partially_paid'])
            ->whereBetween('due_date', [now()->subDays(30), now()])
            ->sum('due_amount');
        $arTotal60 = (float) Invoice::whereIn('status', ['posted', 'partially_paid'])
            ->where('due_date', '<', now()->subDays(60))
            ->sum('due_amount');

        $recentJournals = Journal::with(['lines'])
            ->where('type', '!=', 'void')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn ($j) => [
                'number' => $j->number,
                'date' => $j->date instanceof Carbon ? $j->date->format('d/m') : $j->date,
                'description' => $j->description,
                'source' => $j->source,
                'total' => (float) $j->lines->sum('debit_amount'),
            ])
            ->toArray();

        $cashTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $mFrom = $month->copy()->startOfMonth()->format('Y-m-d');
            $mTo = $month->copy()->endOfMonth()->format('Y-m-d');

            $walletAccountIds = $wallets->pluck('account_id')->filter()->values()->toArray();

            $cashIn = (float) DB::table('journal_lines')
                ->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
                ->whereIn('journal_lines.account_id', $walletAccountIds)
                ->where('journals.source', 'payment')
                ->where('journals.date', '>=', $mFrom)
                ->where('journals.date', '<=', $mTo)
                ->where('journals.type', '!=', 'void')
                ->where('journals.ref_type', 'payments')
                ->whereExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('payments')
                        ->whereColumn('payments.id', 'journals.ref_id')
                        ->where('payments.payable_type', 'invoices');
                })
                ->sum('journal_lines.debit_amount');

            $cashOut = (float) DB::table('journal_lines')
                ->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
                ->whereIn('journal_lines.account_id', $walletAccountIds)
                ->whereIn('journals.source', ['payment', 'expense'])
                ->where('journals.date', '>=', $mFrom)
                ->where('journals.date', '<=', $mTo)
                ->where('journals.type', '!=', 'void')
                ->where(function ($q) {
                    $q->where('journals.source', 'expense')
                        ->orWhere(function ($qq) {
                            $qq->where('journals.source', 'payment')
                                ->where('journals.ref_type', 'payments')
                                ->whereExists(function ($qqq) {
                                    $qqq->select(DB::raw(1))
                                        ->from('payments')
                                        ->whereColumn('payments.id', 'journals.ref_id')
                                        ->where('payments.payable_type', 'purchases');
                                });
                        });
                })
                ->sum('journal_lines.credit_amount');

            $cashTrend[] = [
                'label' => $month->isoFormat('MMM'),
                'cash_in' => round($cashIn, 2),
                'cash_out' => round($cashOut, 2),
            ];
        }

        $cashFlow = JournalService::getCashInOut($dateFrom, $dateTo);

        $cashIn = $cashFlow['cash_in'] ?? [];
        $cashOut = $cashFlow['cash_out'] ?? [];
        $cashInTotal = $cashFlow['cash_in_total'] ?? 0;
        $cashOutTotal = $cashFlow['cash_out_total'] ?? 0;

        $alerts = [];
        $dueSoon = Invoice::whereIn('status', ['posted', 'partially_paid'])
            ->whereBetween('due_date', [now(), now()->addDays(7)])
            ->count();
        if ($dueSoon > 0) {
            $alerts[] = "Ada {$dueSoon} piutang jatuh tempo dalam 7 hari ke depan.";
        }
        if ($netProfit < 0) {
            $alerts[] = 'Bisnis merugi periode ini. Tinjau laporan Laba Rugi.';
        }
        if ($overdueAR > 0) {
            $alerts[] = 'Ada piutang overdue >30 hari sebesar Rp ' . number_format($overdueAR, 0, ',', '.') . '.';
        }

        $this->dashboardData = compact(
            'period', 'revenue', 'expense', 'netProfit',
            'totalCash', 'walletBalances',
            'totalAR', 'totalAP', 'overdueAR', 'overdueAP',
            'arTotalCurrent', 'arTotal30', 'arTotal60',
            'recentJournals', 'cashTrend',
            'cashIn', 'cashOut', 'cashInTotal', 'cashOutTotal',
            'alerts'
        );
    }
}
