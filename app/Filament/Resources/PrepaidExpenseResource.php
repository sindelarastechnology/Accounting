<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PrepaidExpenseResource\Pages;
use App\Models\Period;
use App\Models\PrepaidExpense;
use App\Models\Wallet;
use App\Services\AccrualService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PrepaidExpenseResource extends Resource
{
    protected static ?string $model = PrepaidExpense::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Akuntansi';

    protected static ?string $navigationLabel = 'Prepaid (Bayar Dimuka)';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Pembayaran Dimuka')
                ->schema([
                    Forms\Components\Select::make('period_id')
                        ->label('Periode')
                        ->relationship('period', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Select::make('asset_account_id')
                        ->label('Akun Aset (Dibayar Dimuka)')
                        ->relationship('assetAccount', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Select::make('expense_account_id')
                        ->label('Akun Beban')
                        ->relationship('expenseAccount', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\TextInput::make('description')
                        ->label('Deskripsi')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('total_amount')
                        ->label('Total Jumlah')
                        ->required()
                        ->numeric()
                        ->prefix('Rp')
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            $months = (int) ($get('total_months') ?: 1);
                            if ($state && $months > 0) {
                                $set('monthly_amount', (float) $state / $months);
                            }
                        }),
                    Forms\Components\TextInput::make('total_months')
                        ->label('Lama (Bulan)')
                        ->required()
                        ->numeric()
                        ->minValue(1)
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            $total = (float) ($get('total_amount') ?: 0);
                            if ($total && $state > 0) {
                                $set('monthly_amount', $total / (int) $state);
                            }
                        }),
                    Forms\Components\Select::make('wallet_id')
                        ->label('Dompet (Sumber Pembayaran)')
                        ->options(fn () => Wallet::where('is_active', true)->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText('Kas/bank yang digunakan untuk membayar di muka'),
                ])->columns(2),
            Forms\Components\Section::make('Informasi Tambahan')
                ->schema([
                    Forms\Components\TextInput::make('monthly_amount')
                        ->label('Amortisasi Bulanan')
                        ->numeric()
                        ->prefix('Rp')
                        ->readOnly(),
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'active' => 'Aktif',
                            'fully_amortized' => 'Lunas',
                            'cancelled' => 'Dibatalkan',
                        ])
                        ->default('active'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->searchable()
                    ->limit(35),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('Sisa')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('monthly_amount')
                    ->label('Per Bulan')
                    ->money('IDR', locale: 'id'),
                Tables\Columns\TextColumn::make('months_amortized')
                    ->label('Bulan')
                    ->getStateUsing(fn (PrepaidExpense $r) => "{$r->months_amortized} / {$r->total_months}"),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'warning',
                        'fully_amortized' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Aktif',
                        'fully_amortized' => 'Lunas',
                        'cancelled' => 'Dibatalkan',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('period.name')
                    ->label('Periode')
                    ->searchable()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Aktif',
                        'fully_amortized' => 'Lunas',
                        'cancelled' => 'Dibatalkan',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('amortize')
                    ->label('Amortisasi')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (PrepaidExpense $r) => $r->status === 'active')
                    ->action(function (PrepaidExpense $record) {
                        try {
                            $period = Period::findOrFail($record->period_id);
                            AccrualService::amortizePrepaid($record, $period, Auth::id());
                            Notification::make()
                                ->title('Amortisasi berhasil')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal amortisasi')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('amortize_all')
                    ->label('Amortisasi Semua')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function () {
                        $period = Period::where('is_closed', false)
                            ->where('start_date', '<=', now())
                            ->where('end_date', '>=', now())
                            ->first();

                        if (!$period) {
                            Notification::make()
                                ->title('Tidak ada periode aktif')
                                ->danger()
                                ->send();
                            return;
                        }

                        $results = AccrualService::amortizeAllPrepaids($period, Auth::id());
                        $success = count($results['success']);
                        $failed = count($results['failed']);

                        Notification::make()
                            ->title("Amortisasi: {$success} berhasil, {$failed} gagal")
                            ->body($failed > 0 ? implode("\n", array_map(fn ($r) => $r['description'] . ': ' . $r['error'], $results['failed'])) : null)
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrepaidExpenses::route('/'),
            'create' => Pages\CreatePrepaidExpense::route('/create'),
            'view' => Pages\ViewPrepaidExpense::route('/{record}'),
            'edit' => Pages\EditPrepaidExpense::route('/{record}/edit'),
        ];
    }
}
