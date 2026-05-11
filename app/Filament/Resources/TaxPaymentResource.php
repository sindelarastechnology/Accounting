<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaxPaymentResource\Pages;
use App\Models\Account;
use App\Models\TaxPayment;
use App\Services\TaxPaymentService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TaxPaymentResource extends Resource
{
    protected static ?string $model = TaxPayment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Setoran Pajak';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'document_number';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Select::make('tax_type')
                            ->label('Jenis Pajak')
                            ->options(TaxPayment::TAX_TYPES)
                            ->required()
                            ->searchable(),

                        Forms\Components\Select::make('period_id')
                            ->label('Periode Akuntansi')
                            ->options(fn () => \App\Models\Period::where('is_closed', false)
                                ->orderBy('start_date')
                                ->pluck('name', 'id'))
                            ->default(fn () => \App\Models\Period::where('is_closed', false)
                                ->where('start_date', '<=', now())
                                ->where('end_date', '>=', now())
                                ->value('id')
                                ?? \App\Models\Period::where('is_closed', false)->value('id'))
                            ->searchable()
                            ->required(),

                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Tanggal Pembayaran')
                            ->default(now())
                            ->required(),

                        Forms\Components\Select::make('account_id')
                            ->label('Rekening Kas/Bank')
                            ->options(
                                Account::where('is_header', false)
                                    ->where('is_active', true)
                                    ->where('code', 'like', '11%')
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->required()
                            ->helperText('Rekening yang digunakan untuk membayar'),

                        Forms\Components\TextInput::make('amount')
                            ->label('Jumlah')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->prefix('Rp'),

                        Forms\Components\TextInput::make('reference')
                            ->label('Referensi SSP/NTPN')
                            ->maxLength(100)
                            ->nullable(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->nullable()
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('document_number')
                    ->label('No. Dokumen')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('tax_type')
                    ->label('Jenis Pajak')
                    ->formatStateUsing(fn (string $state) => TaxPayment::TAX_TYPES[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'ppn' => 'info',
                        'pph23' => 'warning',
                        'pph21' => 'warning',
                        'pph4a2' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('period.name')
                    ->label('Periode')
                    ->searchable(),

                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Tanggal Bayar')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR', locale: 'id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('account.name')
                    ->label('Rekening')
                    ->searchable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('reference')
                    ->label('Referensi')
                    ->searchable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'warning',
                        'posted' => 'success',
                        'void' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'posted' => 'Posted',
                        'void' => 'Void',
                        default => ucfirst($state),
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tax_type')
                    ->label('Jenis Pajak')
                    ->options(TaxPayment::TAX_TYPES),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'posted' => 'Posted',
                        'void' => 'Void',
                    ]),

                Tables\Filters\Filter::make('payment_date')
                    ->label('Tanggal Bayar')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')->label('Dari'),
                        Forms\Components\DatePicker::make('date_to')->label('Sampai'),
                    ])
                    ->query(fn ($query, array $data) => $query
                        ->when($data['date_from'], fn ($q, $d) => $q->where('payment_date', '>=', $d))
                        ->when($data['date_to'], fn ($q, $d) => $q->where('payment_date', '<=', $d))),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('post')
                    ->label('Posting')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (TaxPayment $record) => $record->status === 'draft')
                    ->requiresConfirmation()
                    ->modalHeading('Posting Setoran Pajak')
                    ->modalDescription('Setoran pajak akan diposting dan jurnal akuntansi akan dibuat. Lanjutkan?')
                    ->modalSubmitActionLabel('Posting')
                    ->action(function (TaxPayment $record) {
                        try {
                            TaxPaymentService::post($record);
                            Notification::make()
                                ->title('Setoran pajak berhasil diposting')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('void')
                    ->label('Void')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn (TaxPayment $record) => $record->status === 'posted')
                    ->requiresConfirmation()
                    ->modalHeading('Void Setoran Pajak')
                    ->modalDescription('Setoran pajak akan dibatalkan dan jurnal reversal akan dibuat. Lanjutkan?')
                    ->modalSubmitActionLabel('Void')
                    ->action(function (TaxPayment $record) {
                        try {
                            TaxPaymentService::void($record);
                            Notification::make()
                                ->title('Setoran pajak berhasil dibatalkan')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('payment_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTaxPayments::route('/'),
            'create' => Pages\CreateTaxPayment::route('/create'),
            'view' => Pages\ViewTaxPayment::route('/{record}'),
        ];
    }
}
