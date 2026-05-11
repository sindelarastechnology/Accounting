<?php

namespace App\Filament\Resources;

use App\Exceptions\InvalidAccountException;
use App\Exceptions\PeriodClosedException;
use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Account;
use App\Models\Expense;
use App\Models\Period;
use App\Models\Wallet;
use App\Services\ExpenseService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Keuangan';

    protected static ?string $navigationLabel = 'Kas Keluar / Beban';

    protected static ?int $navigationSort = 4;

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
                            ->label('Periode Akuntansi')
                            ->options(function () {
                                return Period::where('is_closed', false)
                                    ->orderBy('start_date')
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->default(function () {
                                return Period::where('is_closed', false)
                                    ->where('start_date', '<=', now())
                                    ->where('end_date', '>=', now())
                                    ->value('id')
                                    ?? Period::where('is_closed', false)->value('id');
                            })
                            ->helperText('Hanya periode aktif yang ditampilkan.')
                            ->required(),

                        Forms\Components\Select::make('account_id')
                            ->label('Akun Beban')
                            ->options(function () {
                                return Account::where('category', 'expense')
                                    ->where('is_header', false)
                                    ->where('is_active', true)
                                    ->orderBy('code')
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->required()
                            ->helperText('Pilih jenis pengeluaran (listrik, air, gaji, dll)'),

                        Forms\Components\View::make('filament.forms.components.expense-quick-accounts')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('wallet_id')
                            ->label('Dibayar dari Dompet/Bank')
                            ->options(fn () => Wallet::active()->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->helperText('Rekening yang digunakan untuk membayar'),

                        Forms\Components\TextInput::make('amount')
                            ->label('Jumlah (Rp)')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->helperText('Jumlah yang dibayarkan'),
                        Forms\Components\Toggle::make('include_tax')
                            ->label('Termasuk PPN 11%')
                            ->default(false)
                            ->helperText('Jika diaktifkan, jumlah akan dipecah menjadi DPP + PPN Masukan'),
                        Forms\Components\Placeholder::make('ppn_preview')
                            ->label('Preview PPN')
                            ->content(function ($get) {
                                $amount = (float) ($get('amount') ?? 0);
                                if (!$amount) return 'Isi jumlah untuk melihat preview';
                                $dpp = round($amount / 1.11, 2);
                                $ppn = $amount - $dpp;
                                return "DPP: Rp " . number_format($dpp, 0, ',', '.') .
                                       " | PPN 11%: Rp " . number_format($ppn, 0, ',', '.');
                            })
                            ->visible(fn ($get) => (bool) $get('include_tax')),

                        Forms\Components\TextInput::make('receipt_number')
                            ->label('No. Referensi/Bukti')
                            ->maxLength(50)
                            ->nullable(),

                        Forms\Components\Textarea::make('name')
                            ->label('Keterangan')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('attachment')
                            ->label('Lampiran Bukti')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->maxSize(2048)
                            ->nullable()
                            ->directory('expenses')
                            ->columnSpanFull(),

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
                Tables\Columns\TextColumn::make('number')
                    ->label('No. Beban')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Keterangan')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn (Expense $record) => $record->name),

                Tables\Columns\TextColumn::make('account.name')
                    ->label('Akun Beban')
                    ->searchable()
                    ->limit(25),

                Tables\Columns\TextColumn::make('wallet.name')
                    ->label('Dibayar dari')
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah')
                    ->getStateUsing(fn (Expense $record) => rupiah($record->amount))
                    ->sortable(),

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

                Tables\Filters\SelectFilter::make('account')
                    ->label('Akun Beban')
                    ->relationship('account', 'name')
                    ->searchable(),

                Tables\Filters\SelectFilter::make('wallet')
                    ->label('Dompet/Bank')
                    ->relationship('wallet', 'name')
                    ->searchable(),

                Tables\Filters\Filter::make('date')
                    ->label('Tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('date_to')
                            ->label('Sampai Tanggal'),
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
                    ->visible(fn (Expense $record) => $record->status === 'draft'),

                Tables\Actions\Action::make('post')
                    ->label('Posting')
                    ->color('primary')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (Expense $record) => $record->status === 'draft')
                    ->requiresConfirmation()
                    ->modalHeading('Posting Beban')
                    ->modalDescription('Beban akan diposting dan jurnal akuntansi akan dibuat (Debit Akun Beban, Kredit Kas/Bank). Lanjutkan?')
                    ->modalSubmitActionLabel('Posting')
                    ->action(function (Expense $record) {
                        try {
                            ExpenseService::postExpense($record);
                            Notification::make()
                                ->title('Beban berhasil diposting')
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

                Tables\Actions\Action::make('cancel')
                    ->label('Batalkan')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn (Expense $record) => in_array($record->status, ['draft', 'posted']))
                    ->modalHeading('Batalkan Beban')
                    ->modalSubmitActionLabel('Batalkan')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Alasan Pembatalan')
                            ->required()
                            ->minLength(10)
                            ->rows(3)
                            ->placeholder('Jelaskan alasan pembatalan (min. 10 karakter)'),
                    ])
                    ->action(function (Expense $record, array $data) {
                        try {
                            ExpenseService::cancelExpense($record);
                            Notification::make()
                                ->title('Beban berhasil dibatalkan')
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
            ->defaultSort('date', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'view' => Pages\ViewExpense::route('/{record}'),
        ];
    }
}
