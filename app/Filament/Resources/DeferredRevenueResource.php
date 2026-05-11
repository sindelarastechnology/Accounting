<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeferredRevenueResource\Pages;
use App\Models\DeferredRevenue;
use App\Models\Period;
use App\Models\Wallet;
use App\Services\AccrualService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class DeferredRevenueResource extends Resource
{
    protected static ?string $model = DeferredRevenue::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Akuntansi';

    protected static ?string $navigationLabel = 'Deferred Revenue (Pendapatan Dimuka)';

    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Pendapatan Diterima Dimuka')
                ->schema([
                    Forms\Components\Select::make('period_id')
                        ->label('Periode')
                        ->relationship('period', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Select::make('liability_account_id')
                        ->label('Akun Kewajiban (Pendapatan Dimuka)')
                        ->relationship('liabilityAccount', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Select::make('revenue_account_id')
                        ->label('Akun Pendapatan')
                        ->relationship('revenueAccount', 'name')
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
                        ->label('Dompet (Penerimaan)')
                        ->options(fn () => Wallet::where('is_active', true)->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText('Kas/bank yang menerima pembayaran di muka'),
                ])->columns(2),
            Forms\Components\Section::make('Informasi Tambahan')
                ->schema([
                    Forms\Components\TextInput::make('monthly_amount')
                        ->label('Pengakuan Bulanan')
                        ->numeric()
                        ->prefix('Rp')
                        ->readOnly(),
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'active' => 'Aktif',
                            'fully_recognized' => 'Diakui Penuh',
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
                Tables\Columns\TextColumn::make('months_recognized')
                    ->label('Bulan')
                    ->getStateUsing(fn (DeferredRevenue $r) => "{$r->months_recognized} / {$r->total_months}"),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'warning',
                        'fully_recognized' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Aktif',
                        'fully_recognized' => 'Diakui Penuh',
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
                        'fully_recognized' => 'Diakui Penuh',
                        'cancelled' => 'Dibatalkan',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('recognize')
                    ->label('Akui Pendapatan')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (DeferredRevenue $r) => $r->status === 'active')
                    ->action(function (DeferredRevenue $record) {
                        try {
                            $period = Period::findOrFail($record->period_id);
                            AccrualService::recognizeDeferred($record, $period, Auth::id());
                            Notification::make()
                                ->title('Pengakuan pendapatan berhasil')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal mengakui pendapatan')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('recognize_all')
                    ->label('Akui Semua')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
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

                        $results = AccrualService::recognizeAllDeferred($period, Auth::id());
                        $success = count($results['success']);
                        $failed = count($results['failed']);

                        Notification::make()
                            ->title("Pengakuan: {$success} berhasil, {$failed} gagal")
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
            'index' => Pages\ListDeferredRevenues::route('/'),
            'create' => Pages\CreateDeferredRevenue::route('/create'),
            'view' => Pages\ViewDeferredRevenue::route('/{record}'),
            'edit' => Pages\EditDeferredRevenue::route('/{record}/edit'),
        ];
    }
}
