<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FundTransferResource\Pages;
use App\Models\Account;
use App\Models\FundTransfer;
use App\Models\Period;
use App\Models\Wallet;
use App\Services\FundTransferService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FundTransferResource extends Resource
{
    protected static ?string $model = FundTransfer::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationGroup = 'Keuangan';

    protected static ?string $navigationLabel = 'Transfer Kas';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'number';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\DatePicker::make('date')
                            ->label('Tanggal')
                            ->default(now())
                            ->required(),

                        Forms\Components\Select::make('period_id')
                            ->label('Periode')
                            ->options(fn () => Period::where('is_closed', false)->orderBy('start_date')->pluck('name', 'id'))
                            ->searchable()
                            ->default(fn () => Period::where('is_closed', false)
                                ->where('start_date', '<=', now())
                                ->where('end_date', '>=', now())
                                ->value('id') ?? Period::where('is_closed', false)->value('id'))
                            ->required()
                            ->visible(fn ($livewire) => Period::where('is_closed', false)->count() > 1),

                        Forms\Components\Select::make('from_wallet_id')
                            ->label('Rekening Asal')
                            ->options(fn () => Wallet::active()->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state && $state === $get('to_wallet_id')) {
                                    $set('to_wallet_id', null);
                                }
                            }),

                        Forms\Components\Select::make('to_wallet_id')
                            ->label('Rekening Tujuan')
                            ->options(fn (callable $get) => Wallet::active()
                                ->when($get('from_wallet_id'), fn ($q, $id) => $q->where('id', '!=', $id))
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                if ($state && $state === $get('from_wallet_id')) {
                                    $set('from_wallet_id', null);
                                }
                            }),

                        Forms\Components\TextInput::make('amount')
                            ->label('Jumlah Transfer (Rp)')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->prefix('Rp'),

                        Forms\Components\TextInput::make('reference')
                            ->label('No. Referensi / Bukti')
                            ->maxLength(100)
                            ->nullable(),

                        Forms\Components\Toggle::make('has_fee')
                            ->label('Ada Biaya Transfer')
                            ->default(false)
                            ->live()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) => $state ?: $set('fee_amount', 0))
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('fee_amount')
                            ->label('Biaya Transfer (Rp)')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->prefix('Rp')
                            ->visible(fn (callable $get) => $get('has_fee'))
                            ->required(fn (callable $get) => $get('has_fee')),

                        Forms\Components\Select::make('fee_account_id')
                            ->label('Akun Biaya Transfer')
                            ->options(fn () => Account::where('category', 'expense')
                                ->where('is_header', false)
                                ->where('is_active', true)
                                ->orderBy('code')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->visible(fn (callable $get) => $get('has_fee'))
                            ->required(fn (callable $get) => $get('has_fee')),

                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->nullable()
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('No. Transfer')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('fromWallet.name')
                    ->label('Dari Rekening')
                    ->searchable(),

                Tables\Columns\TextColumn::make('toWallet.name')
                    ->label('Ke Rekening')
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah')
                    ->getStateUsing(fn (FundTransfer $record) => rupiah($record->amount))
                    ->sortable(),

                Tables\Columns\TextColumn::make('fee_amount')
                    ->label('Biaya')
                    ->getStateUsing(fn (FundTransfer $record) => $record->fee_amount > 0 ? rupiah($record->fee_amount) : '')
                    ->visibleFrom('md'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'posted' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'posted' => 'Posted',
                        'cancelled' => 'Dibatalkan',
                        default => ucfirst($state),
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'posted' => 'Posted',
                        'cancelled' => 'Dibatalkan',
                    ]),
                Tables\Filters\Filter::make('date')
                    ->label('Tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('date_from'),
                        Forms\Components\DatePicker::make('date_to'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['date_from'], fn ($q, $d) => $q->where('date', '>=', $d))
                            ->when($data['date_to'], fn ($q, $d) => $q->where('date', '<=', $d));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (FundTransfer $record) => $record->status === 'draft'),
                Tables\Actions\Action::make('post')
                    ->label('Posting')
                    ->color('primary')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (FundTransfer $record) => $record->status === 'draft')
                    ->requiresConfirmation()
                    ->modalHeading('Posting Transfer')
                    ->modalDescription('Jurnal transfer akan dibuat. Lanjutkan?')
                    ->modalSubmitActionLabel('Posting')
                    ->action(function (FundTransfer $record) {
                        try {
                            FundTransferService::postTransfer($record);
                            Notification::make()->title('Transfer berhasil diposting')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                        }
                    }),
                Tables\Actions\Action::make('cancel')
                    ->label('Batalkan')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn (FundTransfer $record) => in_array($record->status, ['draft', 'posted']))
                    ->modalHeading('Batalkan Transfer')
                    ->modalSubmitActionLabel('Batalkan')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Alasan Pembatalan')
                            ->required()
                            ->minLength(10)
                            ->rows(3),
                    ])
                    ->action(function (FundTransfer $record) {
                        try {
                            FundTransferService::cancelTransfer($record);
                            Notification::make()->title('Transfer berhasil dibatalkan')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('date', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFundTransfers::route('/'),
            'create' => Pages\CreateFundTransfer::route('/create'),
            'view' => Pages\ViewFundTransfer::route('/{record}'),
        ];
    }
}
