<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OtherReceiptResource\Pages;
use App\Models\Account;
use App\Models\Contact;
use App\Models\OtherReceipt;
use App\Models\Period;
use App\Models\Wallet;
use App\Services\OtherReceiptService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OtherReceiptResource extends Resource
{
    protected static ?string $model = OtherReceipt::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Keuangan';

    protected static ?string $navigationLabel = 'Kas Masuk Lainnya';

    protected static ?int $navigationSort = 2;

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

                        Forms\Components\Select::make('receipt_type')
                            ->label('Jenis Penerimaan')
                            ->options([
                                'capital_injection' => 'Tambahan Modal — Setoran modal dari pemilik',
                                'owner_loan' => 'Pinjaman dari Pemilik — Dana pinjaman (harus dikembalikan)',
                                'other_income' => 'Pendapatan Lain — Pendapatan di luar kegiatan utama',
                                'refund' => 'Pengembalian Dana — Restitusi, refund, atau pengembalian uang muka',
                                'other' => 'Lainnya — Penerimaan kas lain yang tidak masuk kategori di atas',
                            ])
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $defaultAccountId = OtherReceipt::defaultCreditAccount($state);
                                if ($defaultAccountId) {
                                    $set('credit_account_id', $defaultAccountId);
                                }
                            }),

                        Forms\Components\Select::make('wallet_id')
                            ->label('Rekening Tujuan (Diterima di)')
                            ->options(fn () => Wallet::active()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),

                        Forms\Components\TextInput::make('amount')
                            ->label('Jumlah (Rp)')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->prefix('Rp'),

                        Forms\Components\Select::make('contact_id')
                            ->label('Dari (Kontak)')
                            ->options(fn () => Contact::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->nullable()
                            ->helperText('Dari siapa penerimaan ini? (Opsional)'),

                        Forms\Components\Select::make('credit_account_id')
                            ->label('Akun Pasangan (Kredit)')
                            ->options(fn () => Account::where('is_header', false)
                                ->where('is_active', true)
                                ->orderBy('code')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->helperText('Akun yang bertambah sebagai pasangan kas masuk ini'),

                        Forms\Components\TextInput::make('reference')
                            ->label('No. Referensi / Bukti')
                            ->maxLength(100)
                            ->nullable(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Keterangan')
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
                    ->label('No. Penerimaan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('receipt_type')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'capital_injection' => 'success',
                        'owner_loan' => 'warning',
                        'other_income' => 'info',
                        'refund' => 'gray',
                        'other' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => OtherReceipt::receiptTypes()[$state] ?? ucfirst($state)),

                Tables\Columns\TextColumn::make('contact.name')
                    ->label('Kontak')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('wallet.name')
                    ->label('Rekening')
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah')
                    ->getStateUsing(fn (OtherReceipt $record) => rupiah($record->amount))
                    ->sortable(),

                Tables\Columns\TextColumn::make('creditAccount.name')
                    ->label('Akun Kredit')
                    ->searchable()
                    ->limit(20),

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
                Tables\Filters\SelectFilter::make('receipt_type')
                    ->label('Jenis')
                    ->options(OtherReceipt::receiptTypes()),
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
                    ->visible(fn (OtherReceipt $record) => $record->status === 'draft'),
                Tables\Actions\Action::make('post')
                    ->label('Posting')
                    ->color('primary')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (OtherReceipt $record) => $record->status === 'draft')
                    ->requiresConfirmation()
                    ->modalHeading('Posting Penerimaan')
                    ->modalDescription('Jurnal akan dibuat (Debit Kas/Bank, Kredit Akun Pasangan). Lanjutkan?')
                    ->modalSubmitActionLabel('Posting')
                    ->action(function (OtherReceipt $record) {
                        try {
                            OtherReceiptService::postReceipt($record);
                            Notification::make()->title('Penerimaan berhasil diposting')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                        }
                    }),
                Tables\Actions\Action::make('cancel')
                    ->label('Batalkan')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn (OtherReceipt $record) => in_array($record->status, ['draft', 'posted']))
                    ->modalHeading('Batalkan Penerimaan')
                    ->modalSubmitActionLabel('Batalkan')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Alasan Pembatalan')
                            ->required()
                            ->minLength(10)
                            ->rows(3),
                    ])
                    ->action(function (OtherReceipt $record) {
                        try {
                            OtherReceiptService::cancelReceipt($record);
                            Notification::make()->title('Penerimaan berhasil dibatalkan')->success()->send();
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
            'index' => Pages\ListOtherReceipts::route('/'),
            'create' => Pages\CreateOtherReceipt::route('/create'),
            'view' => Pages\ViewOtherReceipt::route('/{record}'),
        ];
    }
}
