<?php

namespace App\Filament\Pages;

use App\Models\Period;
use App\Models\Wallet;
use App\Services\ReconciliationService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class BankReconciliationPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Akuntansi';

    protected static ?string $navigationLabel = 'Rekonsiliasi Bank';

    protected static ?int $navigationSort = 8;

    protected static string $view = 'filament.pages.bank-reconciliation';

    public ?int $period_id = null;

    public ?int $wallet_id = null;

    public ?float $statement_balance = null;

    public array $reconciled_ids = [];

    public function mount(): void
    {
        $this->period_id = Period::where('is_closed', false)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->value('id')
            ?? Period::orderBy('start_date', 'desc')->value('id');

        $this->wallet_id = Wallet::active()->where('type', 'bank')->value('id')
            ?? Wallet::active()->value('id');
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('period_id')
                ->label('Periode')
                ->options(fn () => Period::orderBy('start_date', 'desc')->pluck('name', 'id'))
                ->searchable()
                ->live()
                ->required(),
            Select::make('wallet_id')
                ->label('Dompet / Bank')
                ->options(fn () => Wallet::active()->pluck('name', 'id'))
                ->searchable()
                ->live()
                ->required(),
            TextInput::make('statement_balance')
                ->label('Saldo Menurut Bank / Statement')
                ->numeric()
                ->prefix('Rp')
                ->live()
                ->placeholder('Masukkan saldo akhir menurut bank...'),
        ])->columns(3);
    }

    public function getData(): array
    {
        if (!$this->period_id || !$this->wallet_id) {
            return [];
        }

        $wallet = Wallet::find($this->wallet_id);
        $period = Period::find($this->period_id);

        if (!$wallet || !$period) {
            return [];
        }

        $data = ReconciliationService::getReconciliationData($wallet, $period);
        $data['statement_balance'] = $this->statement_balance;

        $data['difference'] = null;
        if ($this->statement_balance !== null) {
            $data['difference'] = (float) $this->statement_balance - $data['ending_balance'];
        }

        $data['is_balanced'] = $data['difference'] !== null && abs($data['difference']) < 0.01;

        // Pre-populate reconciled_ids
        $this->reconciled_ids = collect($data['lines'])
            ->whereNotNull('reconciled_at')
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->toArray();

        return $data;
    }

    public function saveReconciliation(): void
    {
        if (!$this->period_id || !$this->wallet_id) {
            return;
        }

        $wallet = Wallet::findOrFail($this->wallet_id);
        $period = Period::findOrFail($this->period_id);

        try {
            ReconciliationService::saveReconciliation(
                $wallet,
                $period,
                $this->reconciled_ids,
                Auth::id()
            );

            $data = $this->getData();
            $message = 'Rekonsiliasi tersimpan. ' . $data['reconciled_count'] . '/' . $data['total_count'] . ' transaksi dicocokkan.';

            if ($this->statement_balance !== null) {
                if (abs($data['difference']) < 0.01) {
                    Notification::make()
                        ->title('Rekonsiliasi SELESAI — Saldo match!')
                        ->body($message)
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Rekonsiliasi disimpan, ada selisih Rp ' . number_format(abs($data['difference']), 0, ',', '.'))
                        ->body($message)
                        ->warning()
                        ->send();
                }
            } else {
                Notification::make()
                    ->title('Rekonsiliasi tersimpan')
                    ->body($message)
                    ->success()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal menyimpan rekonsiliasi')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
