<?php

namespace App\Filament\Pages;

use App\Models\Period;
use App\Services\AccrualService;
use App\Services\FixedAssetService;
use App\Services\JournalService;
use App\Services\PeriodService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class PeriodClosingChecklistPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Closing Checklist';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.period-closing-checklist';

    public ?int $period_id = null;

    public array $steps = [];

    public array $stepStatus = [];

    protected function getSteps(): array
    {
        return [
            [
                'id' => 'bank_reconciliation',
                'label' => 'Rekonsiliasi Bank',
                'description' => 'Cocokkan jurnal kas/bank dengan bank statement. Pastikan saldo kas sesuai.',
                'action_label' => 'Cek Status',
                'action_type' => 'run',
                'action_method' => 'checkBankReconciliation',
                'secondary_action_label' => 'Buka Rekonsiliasi',
                'secondary_action_url' => \App\Filament\Pages\BankReconciliationPage::getUrl(),
            ],
            [
                'id' => 'ar_aging',
                'label' => 'Cek Aging AR',
                'description' => 'Pastikan semua piutang usaha tertagih dan tidak ada piutang macet.',
                'action_label' => 'Cek Aging AR',
                'action_type' => 'link',
                'action_url' => \App\Filament\Pages\AgingReportPage::getUrl() . '?type=receivable',
            ],
            [
                'id' => 'ap_aging',
                'label' => 'Cek Aging AP',
                'description' => 'Pastikan semua hutang usaha tercatat dengan benar.',
                'action_label' => 'Cek Aging AP',
                'action_type' => 'link',
                'action_url' => \App\Filament\Pages\AgingReportPage::getUrl() . '?type=payable',
            ],
            [
                'id' => 'depreciation',
                'label' => 'Hitung Penyusutan Aset Tetap',
                'description' => 'Jalankan penyusutan aset tetap untuk periode ini.',
                'action_label' => 'Jalankan Penyusutan',
                'action_type' => 'run',
                'action_method' => 'runDepreciation',
            ],
            [
                'id' => 'ending_inventory',
                'label' => 'Cek Persediaan Akhir',
                'description' => 'Cocokkan stok fisik dengan sistem. Jalankan stock opname jika perlu.',
                'action_label' => 'Lihat Stok',
                'action_type' => 'run',
                'action_method' => 'checkInventory',
            ],
            [
                'id' => 'adjusting_entries',
                'label' => 'Jurnal Penyesuaian (Akrual & Prepaid)',
                'description' => 'Jalankan amortisasi prepaid dan pengakuan deferred revenue untuk periode ini.',
                'action_label' => 'Jalankan Akrual Bulanan',
                'action_type' => 'run',
                'action_method' => 'runMonthlyAccruals',
            ],
            [
                'id' => 'tax_check',
                'label' => 'Cek PPN & PPh',
                'description' => 'Pastikan semua PPN dan PPh telah tercatat dengan benar.',
                'action_label' => 'Cek Pajak',
                'action_type' => 'run',
                'action_method' => 'checkTax',
            ],
            [
                'id' => 'balance_check',
                'label' => 'Cek Selisih Neraca',
                'description' => 'Pastikan Neraca balance (Total Aset = Total Liabilitas + Ekuitas).',
                'action_label' => 'Cek Neraca',
                'action_type' => 'run',
                'action_method' => 'checkBalance',
            ],
            [
                'id' => 'close_period',
                'label' => 'Tutup Periode',
                'description' => 'Tutup periode akuntansi. Pendapatan & beban akan dipindah ke Laba Ditahan.',
                'action_label' => 'Tutup Periode',
                'action_type' => 'run',
                'action_method' => 'closePeriod',
                'requires_confirmation' => true,
            ],
            [
                'id' => 'open_next_period',
                'label' => 'Buka Periode Baru',
                'description' => 'Buat periode baru dan carry forward saldo Aset/Liabilitas/Ekuitas.',
                'action_label' => 'Buka Periode',
                'action_type' => 'run',
                'action_method' => 'openNextPeriod',
                'requires_confirmation' => true,
            ],
        ];
    }

    public function mount(): void
    {
        $this->period_id = Period::where('is_closed', false)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->value('id')
            ?? Period::orderBy('start_date', 'desc')->value('id');

        $this->steps = $this->getSteps();
        $this->loadStatus();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('period_id')
                ->label('Periode')
                ->options(fn () => Period::orderBy('start_date', 'desc')->pluck('name', 'id'))
                ->searchable()
                ->live()
                ->afterStateUpdated(fn () => $this->loadStatus()),
        ]);
    }

    public function updatedPeriodId(): void
    {
        $this->loadStatus();
    }

    public function loadStatus(): void
    {
        $this->stepStatus = [];

        if (!$this->period_id) {
            return;
        }

        $period = Period::find($this->period_id);
        if (!$period) {
            return;
        }

        $this->stepStatus = [
            'bank_reconciliation' => 'pending',
            'ar_aging' => 'pending',
            'ap_aging' => 'pending',
            'depreciation' => 'pending',
            'ending_inventory' => 'pending',
            'adjusting_entries' => 'pending',
            'tax_check' => 'pending',
            'balance_check' => 'pending',
            'close_period' => $period->is_closed ? 'completed' : 'pending',
            'open_next_period' => 'pending',
        ];

        // Auto-detect: if period is closed, mark close_period as done
        if ($period->is_closed) {
            $this->stepStatus['close_period'] = 'completed';

            // Check if next period exists
            $nextPeriod = PeriodService::getNextPeriod($period);
            if ($nextPeriod) {
                $this->stepStatus['open_next_period'] = 'completed';
            }
        }
    }

    public function getStepBadge(string $status): string
    {
        return match ($status) {
            'completed' => '✓ Selesai',
            'pending' => '⋯',
            default => '⋯',
        };
    }

    public function getStepColor(string $status): string
    {
        return match ($status) {
            'completed' => 'success',
            'pending' => 'gray',
            default => 'gray',
        };
    }

    public function checkBankReconciliation(): void
    {
        try {
            $period = Period::find($this->period_id);
            if (!$period) {
                return;
            }

            $bankWallets = \App\Models\Wallet::active()->where('type', 'bank')->get();

            if ($bankWallets->isEmpty()) {
                $bankWallets = \App\Models\Wallet::active()->get();
            }

            $allReconciled = true;
            $totalUnreconciled = 0;

            foreach ($bankWallets as $wallet) {
                $dateFrom = $period->start_date instanceof \Carbon\Carbon
                    ? $period->start_date->format('Y-m-d')
                    : $period->start_date;
                $dateTo = $period->end_date instanceof \Carbon\Carbon
                    ? $period->end_date->format('Y-m-d')
                    : $period->end_date;

                $unreconciledCount = \App\Models\JournalLine::where('wallet_id', $wallet->id)
                    ->whereNull('reconciled_at')
                    ->whereHas('journal', function ($q) use ($dateFrom, $dateTo) {
                        $q->where('type', '!=', 'void')
                            ->whereBetween('date', [$dateFrom, $dateTo]);
                    })
                    ->count();

                $totalUnreconciled += $unreconciledCount;
                if ($unreconciledCount > 0) {
                    $allReconciled = false;
                }
            }

            if ($allReconciled || $totalUnreconciled === 0) {
                $this->stepStatus['bank_reconciliation'] = 'completed';
                Notification::make()
                    ->title('Semua transaksi bank sudah direkonsiliasi ✓')
                    ->success()
                    ->send();
            } else {
                $walletNames = $bankWallets->pluck('name')->implode(', ');
                Notification::make()
                    ->title("{$totalUnreconciled} transaksi belum direkonsiliasi")
                    ->body("Wallet: {$walletNames}")
                    ->warning()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal cek rekonsiliasi')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function runDepreciation(): void
    {
        try {
            $result = FixedAssetService::calculateAllMonthlyDepreciation();

            $successCount = count($result['success'] ?? []);
            $failCount = count($result['failed'] ?? []);

            if ($successCount > 0) {
                $this->stepStatus['depreciation'] = 'completed';
                Notification::make()
                    ->title("Penyusutan berhasil: {$successCount} aset")
                    ->body($failCount > 0 ? "{$failCount} aset gagal" : null)
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Tidak ada penyusutan yang perlu dicatat')
                    ->info()
                    ->send();
            }

            if ($failCount > 0) {
                foreach ($result['failed'] as $fail) {
                    Notification::make()
                        ->title("Gagal: {$fail['name']}")
                        ->body($fail['error'])
                        ->danger()
                        ->send();
                }
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal menjalankan penyusutan')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function checkInventory(): void
    {
        $period = Period::find($this->period_id);
        if (!$period) {
            return;
        }

        $closingInventory = \App\Services\FifoCostService::getClosingInventoryValue(
            $period->end_date instanceof \Carbon\Carbon
                ? $period->end_date->format('Y-m-d')
                : $period->end_date
        );

        Notification::make()
            ->title('Nilai Persediaan Akhir (FIFO)')
            ->body('Rp ' . number_format($closingInventory, 0, ',', '.'))
            ->info()
            ->send();

        $this->stepStatus['ending_inventory'] = 'completed';
    }

    public function checkTax(): void
    {
        try {
            $ppnReport = JournalService::getPpnReport($this->period_id);

            $totalKeluaran = (float) ($ppnReport['total_ppn_keluaran'] ?? 0);
            $totalMasukan = (float) ($ppnReport['total_ppn_masukan'] ?? 0);
            $kurangBayar = (float) ($ppnReport['ppn_kurang_bayar'] ?? 0);
            $lebihBayar = (float) ($ppnReport['ppn_lebih_bayar'] ?? 0);

            $message = "PPN Keluaran: Rp " . number_format($totalKeluaran, 0, ',', '.') . "\n"
                . "PPN Masukan: Rp " . number_format($totalMasukan, 0, ',', '.') . "\n";

            if ($kurangBayar > 0) {
                $message .= "PPN Kurang Bayar: Rp " . number_format($kurangBayar, 0, ',', '.');
            } elseif ($lebihBayar > 0) {
                $message .= "PPN Lebih Bayar: Rp " . number_format($lebihBayar, 0, ',', '.');
            } else {
                $message .= "PPN: NIHIL";
            }

            Notification::make()
                ->title('Ringkasan PPN')
                ->body($message)
                ->info()
                ->send();

            $this->stepStatus['tax_check'] = 'completed';
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal cek pajak')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function checkBalance(): void
    {
        try {
            $period = Period::find($this->period_id);
            if (!$period) {
                return;
            }

            $balanceSheet = JournalService::getBalanceSheet(
                $this->period_id,
                $period->end_date instanceof \Carbon\Carbon
                    ? $period->end_date->format('Y-m-d')
                    : $period->end_date
            );

            if ($balanceSheet['is_balanced']) {
                Notification::make()
                    ->title('Neraca Balance ✓')
                    ->body('Total Aset = Total Liabilitas + Ekuitas')
                    ->success()
                    ->send();
                $this->stepStatus['balance_check'] = 'completed';
            } else {
                $selisih = abs($balanceSheet['difference']);
                Notification::make()
                    ->title('Neraca TIDAK Balance ✗')
                    ->body('Selisih: Rp ' . number_format($selisih, 0, ',', '.'))
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal cek neraca')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function runMonthlyAccruals(): void
    {
        try {
            $period = Period::find($this->period_id);
            if (!$period) {
                return;
            }

            $results = AccrualService::createMonthlyAccruals($period, Auth::id());

            $prepaidSuccess = count($results['prepaid']['success']);
            $prepaidFailed = count($results['prepaid']['failed']);
            $deferredSuccess = count($results['deferred']['success']);
            $deferredFailed = count($results['deferred']['failed']);
            $totalSuccess = $prepaidSuccess + $deferredSuccess;
            $totalFailed = $prepaidFailed + $deferredFailed;

            if ($totalSuccess > 0 || $totalFailed === 0) {
                $this->stepStatus['adjusting_entries'] = 'completed';

                $body = [];
                if ($prepaidSuccess > 0) {
                    $body[] = "Prepaid diamortisasi: {$prepaidSuccess}";
                }
                if ($deferredSuccess > 0) {
                    $body[] = "Deferred revenue diakui: {$deferredSuccess}";
                }
                if ($totalFailed > 0) {
                    $body[] = "Gagal: {$totalFailed}";
                }

                Notification::make()
                    ->title('Akrual bulanan selesai')
                    ->body(implode(', ', $body))
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Tidak ada akrual yang perlu dicatat')
                    ->info()
                    ->send();
            }

            if ($totalFailed > 0) {
                foreach (array_merge($results['prepaid']['failed'], $results['deferred']['failed']) as $fail) {
                    Notification::make()
                        ->title("Gagal: {$fail['description']}")
                        ->body($fail['error'] ?? 'Unknown error')
                        ->danger()
                        ->send();
                }
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal menjalankan akrual bulanan')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function closePeriod(): void
    {
        try {
            $period = Period::findOrFail($this->period_id);

            if ($period->is_closed) {
                Notification::make()
                    ->title('Periode sudah ditutup')
                    ->warning()
                    ->send();
                return;
            }

            PeriodService::closePeriod($period, Auth::id());
            $this->stepStatus['close_period'] = 'completed';
            $this->stepStatus['open_next_period'] = 'completed';

            Notification::make()
                ->title('Periode berhasil ditutup')
                ->body('Saldo otomatis di-carry forward ke periode berikutnya.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal menutup periode')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function openNextPeriod(): void
    {
        try {
            $period = Period::findOrFail($this->period_id);

            if (!$period->is_closed) {
                Notification::make()
                    ->title('Tutup periode terlebih dahulu')
                    ->warning()
                    ->send();
                return;
            }

            $nextPeriod = PeriodService::getNextPeriod($period);

            if (!$nextPeriod) {
                PeriodService::createNextPeriod($period);
                Notification::make()
                    ->title('Periode baru berhasil dibuat')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Periode berikutnya sudah ada')
                    ->body($nextPeriod->name)
                    ->info()
                    ->send();
            }

            $this->stepStatus['open_next_period'] = 'completed';
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal membuka periode baru')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
