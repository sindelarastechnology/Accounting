<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountResource\Pages;
use App\Models\Account;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Daftar Akun';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Kode Akun')
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Akun')
                            ->required()
                            ->maxLength(150),
                    ]),
                Forms\Components\Select::make('category')
                    ->label('Kategori')
                    ->options(Account::categories())
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if ($state) {
                            $set('normal_balance', Account::normalBalanceForCategory($state));
                        }
                    }),
                Forms\Components\Select::make('normal_balance')
                    ->label('Posisi Normal')
                    ->options([
                        'debit' => 'Debit',
                        'credit' => 'Kredit',
                    ])
                    ->required(),
                Forms\Components\Select::make('parent_id')
                    ->label('Akun Induk')
                    ->options(fn () => Account::header()->active()->pluck('name', 'id'))
                    ->searchable()
                    ->placeholder('Tidak ada (akun induk)'),
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\Toggle::make('is_header')
                            ->label('Akun Induk')
                            ->helperText('Jika ya, tidak bisa dipakai di jurnal')
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->required(),
                    ]),
                Forms\Components\Textarea::make('description')
                    ->label('Keterangan')
                    ->columnSpanFull()
                    ->rows(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->weight(fn ($record) => $record->is_header ? 'bold' : null),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Akun')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->name),
                Tables\Columns\TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'asset' => 'success',
                        'liability' => 'danger',
                        'equity' => 'info',
                        'revenue' => 'primary',
                        'cogs' => 'warning',
                        'expense' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => Account::categories()[$state] ?? $state),
                Tables\Columns\TextColumn::make('normal_balance')
                    ->label('Normal')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'debit' ? 'warning' : 'success')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Akun Induk')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_header')
                    ->label('Header')
                    ->boolean(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktif'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Kategori')
                    ->options(Account::categories()),
                Tables\Filters\TernaryFilter::make('is_header')
                    ->label('Jenis Akun')
                    ->trueLabel('Akun Induk')
                    ->falseLabel('Akun Detail'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('code');
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
            'index' => Pages\ListAccounts::route('/'),
            'create' => Pages\CreateAccount::route('/create'),
            'edit' => Pages\EditAccount::route('/{record}/edit'),
        ];
    }
}
