<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletResource\Pages;
use App\Models\Account;
use App\Models\JournalLine;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WalletResource extends Resource
{
    protected static ?string $model = Wallet::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $navigationGroup = 'Keuangan';

    protected static ?string $navigationLabel = 'Dompet & Bank';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        $isEdit = $form->getRecord() !== null;

        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Dompet')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('Contoh: Kas Kecil, BCA, GoPay'),
                        Forms\Components\Select::make('type')
                            ->label('Tipe')
                            ->options(Wallet::types())
                            ->required()
                            ->live(),
                    ]),
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\TextInput::make('bank_name')
                            ->label('Nama Bank')
                            ->maxLength(100)
                            ->visible(fn (Forms\Get $get) => $get('type') === 'bank')
                            ->placeholder('Contoh: Bank Central Asia'),
                        Forms\Components\TextInput::make('account_number')
                            ->label('No. Rekening')
                            ->maxLength(50)
                            ->visible(fn (Forms\Get $get) => $get('type') === 'bank')
                            ->placeholder('Contoh: 1234567890'),
                        Forms\Components\TextInput::make('account_holder')
                            ->label('Atas Nama')
                            ->maxLength(100)
                            ->visible(fn (Forms\Get $get) => $get('type') === 'bank')
                            ->placeholder('Contoh: PT Onezie Accounting'),
                    ]),
                Forms\Components\Select::make('account_id')
                    ->label('Akun COA')
                    ->options(fn () => Account::where('category', 'asset')->detail()->active()->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->helperText('Hanya menampilkan akun kategori Aset (detail)'),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('opening_balance')
                            ->label('Saldo Awal')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->prefix('Rp')
                            ->live()
                            ->disabled($isEdit)
                            ->helperText($isEdit ? 'Saldo awal tidak dapat diubah. Gunakan jurnal penyesuaian jika diperlukan.' : ''),
                        Forms\Components\Select::make('equity_account_id')
                            ->label('Akun Sumber Saldo Awal')
                            ->options(fn () => Account::whereIn('category', ['equity', 'liability'])->detail()->active()->pluck('name', 'id'))
                            ->searchable()
                            ->required(fn (Forms\Get $get) => (float) ($get('opening_balance') ?? 0) > 0)
                            ->visible(fn (Forms\Get $get) => (float) ($get('opening_balance') ?? 0) > 0)
                            ->helperText($isEdit
                                ? 'Saldo awal tidak dapat diubah. Gunakan jurnal penyesuaian jika diperlukan.'
                                : 'Saldo awal akan di-Kredit ke akun ini (mis. Modal Pemilik, Laba Ditahan, atau Hutang Bank)')
                            ->disabled($isEdit),
                    ]),
                Forms\Components\Grid::make(1)
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->required(),
                    ]),
                Forms\Components\Textarea::make('notes')
                    ->label('Catatan')
                    ->columnSpanFull()
                    ->rows(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cash' => 'success',
                        'bank' => 'primary',
                        'ewallet' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => Wallet::types()[$state] ?? $state),
                Tables\Columns\TextColumn::make('bank_name')
                    ->label('Bank')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('account_number')
                    ->label('No. Rekening')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('account.name')
                    ->label('Akun COA')
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_balance')
                    ->label('Saldo Berjalan')
                    ->getStateUsing(function ($record) {
                        $balance = (float) $record->opening_balance;
                        $balance += JournalLine::where('wallet_id', $record->id)
                            ->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
                            ->whereNotIn('journals.type', ['void', 'opening'])
                            ->selectRaw('COALESCE(SUM(debit_amount - credit_amount), 0) as net')
                            ->value('net');
                        return 'Rp ' . number_format($balance, 0, ',', '.');
                    })
                    ->sortable(query: function ($query, string $direction) {
                        $query->orderByRaw('(SELECT COALESCE(SUM(journal_lines.debit_amount - journal_lines.credit_amount), 0) FROM journal_lines JOIN journals ON journal_lines.journal_id = journals.id WHERE journal_lines.wallet_id = wallets.id AND journals.type NOT IN ("void", "opening")) + wallets.opening_balance ' . $direction);
                    }),
                Tables\Columns\TextColumn::make('equityAccount.name')
                    ->label('Akun Lawan')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('opening_balance')
                    ->label('Saldo Awal')
                    ->money('IDR', locale: 'id'),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktif'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe')
                    ->options(Wallet::types()),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWallets::route('/'),
            'create' => Pages\CreateWallet::route('/create'),
            'edit' => Pages\EditWallet::route('/{record}/edit'),
        ];
    }
}
