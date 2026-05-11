<?php

namespace App\Filament\Pages;

use App\Models\Account;
use App\Models\Journal;
use App\Models\OpeningBalance;
use App\Models\Period;
use App\Services\JournalService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OpeningBalancePage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Saldo Awal Akun';

    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.pages.opening-balance';

    public $periodId = null;

    public $accounts = [];

    public $balanceInputs = [];

    public $totalDebit = 0;

    public $totalCredit = 0;

    public function mount(): void
    {
        $this->loadAccounts();
        $this->loadPeriods();
        $this->loadOpeningBalances();
    }

    protected function loadPeriods(): void
    {
        $periods = Period::orderBy('start_date', 'asc')->get();
        if ($periods->isNotEmpty() && !$this->periodId) {
            $this->periodId = $periods->first()->id;
        }
    }

    protected function loadAccounts(): void
    {
        $accounts = Account::orderBy('code')->get();

        $this->accounts = $accounts->map(function ($account) {
            return [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'category' => $account->category,
                'category_label' => Account::categories()[$account->category] ?? $account->category,
                'normal_balance' => $account->normal_balance,
                'is_header' => $account->is_header,
            ];
        })->toArray();
    }

    public function updatedPeriodId(): void
    {
        $this->loadOpeningBalances();
    }

    public function loadOpeningBalances(): void
    {
        if (!$this->periodId) {
            return;
        }

        $existingBalances = OpeningBalance::where('period_id', $this->periodId)
            ->get()
            ->keyBy('account_id');

        $this->balanceInputs = [];
        foreach ($this->accounts as $account) {
            if ($account['is_header']) {
                continue;
            }

            $balance = $existingBalances->get($account['id']);

            if ($balance) {
                if ($balance->position === 'debit') {
                    $this->balanceInputs[$account['id']] = [
                        'debit' => $balance->amount,
                        'credit' => 0,
                    ];
                } else {
                    $this->balanceInputs[$account['id']] = [
                        'debit' => 0,
                        'credit' => $balance->amount,
                    ];
                }
            } else {
                $this->balanceInputs[$account['id']] = [
                    'debit' => 0,
                    'credit' => 0,
                ];
            }
        }

        $this->recalculateTotals();
    }

    public function updatedBalanceInputs(): void
    {
        $this->recalculateTotals();
    }

    public function recalculateTotals(): void
    {
        $this->totalDebit = 0;
        $this->totalCredit = 0;

        foreach ($this->balanceInputs as $accountId => $values) {
            $debit = (float) ($values['debit'] ?? 0);
            $credit = (float) ($values['credit'] ?? 0);

            if ($debit > 0 && $credit > 0) {
                continue;
            }

            $this->totalDebit += $debit;
            $this->totalCredit += $credit;
        }
    }

    public function saveOpeningBalances(): void
    {
        if (!$this->periodId) {
            Notification::make()
                ->title('Pilih periode terlebih dahulu')
                ->danger()
                ->send();
            return;
        }

        foreach ($this->balanceInputs as $accountId => $values) {
            $debit = (float) ($values['debit'] ?? 0);
            $credit = (float) ($values['credit'] ?? 0);

            if ($debit > 0 && $credit > 0) {
                $account = Account::find($accountId);
                Notification::make()
                    ->title('Invalid input')
                    ->body("Akun [{$account->code}] {$account->name}: tidak boleh memiliki saldo di debit DAN kredit sekaligus.")
                    ->danger()
                    ->send();
                return;
            }
        }

        $diff = abs($this->totalDebit - $this->totalCredit);
        if ($diff > 0.01) {
            $selisih = $this->totalDebit - $this->totalCredit;
            Notification::make()
                ->title('Saldo tidak balance')
                ->body("Total debit (" . $this->formatRupiah($this->totalDebit) . ") tidak sama dengan total kredit (" . $this->formatRupiah($this->totalCredit) . "). Selisih: " . $this->formatRupiah(abs($selisih)) . ".")
                ->danger()
                ->send();
            return;
        }

        $hasExisting = OpeningBalance::where('period_id', $this->periodId)->exists();

        DB::beginTransaction();
        try {
            OpeningBalance::where('period_id', $this->periodId)->delete();

            // Void jurnal saldo awal sebelumnya untuk mencegah duplikasi
            $oldOpeningJournals = Journal::where('period_id', $this->periodId)
                ->where('type', 'opening')
                ->where('source', 'system')
                ->get();
            foreach ($oldOpeningJournals as $oldJournal) {
                if ($oldJournal->type !== 'void') {
                    JournalService::voidJournal($oldJournal, "Perbarui saldo awal periode {$period->name}");
                }
            }

            $journalLines = [];
            $period = Period::find($this->periodId);

            foreach ($this->balanceInputs as $accountId => $values) {
                $debit = (float) ($values['debit'] ?? 0);
                $credit = (float) ($values['credit'] ?? 0);

                if ($debit <= 0 && $credit <= 0) {
                    continue;
                }

                if ($debit > 0) {
                    OpeningBalance::create([
                        'period_id' => $this->periodId,
                        'account_id' => $accountId,
                        'amount' => $debit,
                        'position' => 'debit',
                    ]);

                    $journalLines[] = [
                        'account_id' => $accountId,
                        'debit_amount' => $debit,
                        'credit_amount' => 0,
                    ];
                }

                if ($credit > 0) {
                    OpeningBalance::create([
                        'period_id' => $this->periodId,
                        'account_id' => $accountId,
                        'amount' => $credit,
                        'position' => 'credit',
                    ]);

                    $journalLines[] = [
                        'account_id' => $accountId,
                        'debit_amount' => 0,
                        'credit_amount' => $credit,
                    ];
                }
            }

            if (count($journalLines) >= 2) {
                JournalService::createJournal([
                    'date' => $period->start_date,
                    'period_id' => $this->periodId,
                    'description' => "Saldo Awal — {$period->name}",
                    'source' => 'system',
                    'type' => 'opening',
                    'created_by' => auth()->id(),
                ], $journalLines);
            }

            DB::commit();

            $message = $hasExisting
                ? 'Saldo awal berhasil diperbarui.'
                : 'Saldo awal berhasil disimpan.';

            Notification::make()
                ->title($message)
                ->success()
                ->send();
        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()
                ->title('Gagal menyimpan saldo awal')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function resetBalances(): void
    {
        $this->loadOpeningBalances();
        Notification::make()
            ->title('Input telah direset')
            ->info()
            ->send();
    }

    public function importFromExcel(): void
    {
        // Handled via modal form
    }

    public function processImportedFile($file): void
    {
        try {
            $filePath = $file->storeAs('temp', 'opening_balance_import_' . time() . '.' . $file->getClientOriginalExtension(), 'local');

            $rows = Excel::toArray([], storage_path('app/' . $filePath));
            \Storage::disk('local')->delete($filePath);

            if (empty($rows) || empty($rows[0])) {
                Notification::make()
                    ->title('File kosong')
                    ->body('File Excel tidak memiliki data.')
                    ->warning()
                    ->send();
                return;
            }

            $accountByCode = [];
            foreach ($this->accounts as $account) {
                if (!$account['is_header']) {
                    $accountByCode[$account['code']] = $account['id'];
                }
            }

            foreach ($rows[0] as $rowIndex => $row) {
                if ($rowIndex === 0) {
                    continue;
                }

                $code = trim($row[0] ?? '');
                $debit = (float) ($row[1] ?? 0);
                $credit = (float) ($row[2] ?? 0);

                if (!$code || ($debit <= 0 && $credit <= 0)) {
                    continue;
                }

                if (isset($accountByCode[$code])) {
                    $accountId = $accountByCode[$code];

                    if ($debit > 0) {
                        $this->balanceInputs[$accountId] = [
                            'debit' => $debit,
                            'credit' => 0,
                        ];
                    } elseif ($credit > 0) {
                        $this->balanceInputs[$accountId] = [
                            'debit' => 0,
                            'credit' => $credit,
                        ];
                    }
                }
            }

            $this->recalculateTotals();

            Notification::make()
                ->title('Import berhasil')
                ->body('Data saldo awal telah diisi dari file Excel.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal mengimport file')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function formatRupiah($amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('importExcel')
                ->label('Import dari Excel')
                ->icon('heroicon-o-document-arrow-up')
                ->color('secondary')
                ->form([
                    FileUpload::make('file')
                        ->label('File Excel (.xlsx)')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                        ])
                        ->maxSize(5120)
                        ->required(),
                ])
                ->action(function (array $data) {
                    if (isset($data['file']) && $data['file']) {
                        $this->processImportedFile($data['file']);
                    }
                }),

            Action::make('save')
                ->label('Simpan Saldo Awal')
                ->icon('heroicon-o-check')
                ->color('success')
                ->action(function () {
                    $this->saveOpeningBalances();
                }),

            Action::make('reset')
                ->label('Reset')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->action(function () {
                    $this->resetBalances();
                }),
        ];
    }
}
