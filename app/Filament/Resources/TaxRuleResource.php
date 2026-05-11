<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaxRuleResource\Pages;
use App\Models\Account;
use App\Models\TaxRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TaxRuleResource extends Resource
{
    protected static ?string $model = TaxRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Aturan Pajak';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Kode Pajak')
                            ->required()
                            ->maxLength(30)
                            ->unique(ignoreRecord: true)
                            ->placeholder('Contoh: PPN_11, PPH23_2'),
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Pajak')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('Contoh: PPN 11%'),
                    ]),
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Jenis Pajak')
                            ->options(TaxRule::types())
                            ->required()
                            ->live(),
                        Forms\Components\Select::make('module')
                            ->label('Modul')
                            ->options(TaxRule::modules())
                            ->required(),
                        Forms\Components\Select::make('method')
                            ->label('Metode')
                            ->options(TaxRule::methods())
                            ->required()
                            ->live(),
                    ]),
                Forms\Components\TextInput::make('rate')
                    ->label('Tarif (%)')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->step(0.01)
                    ->suffix('%'),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Select::make('debit_account_id')
                            ->label('Akun Debit')
                            ->options(fn () => Account::detail()->active()->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->helperText('Akun yang di-debit saat pajak dijurnal'),
                        Forms\Components\Select::make('credit_account_id')
                            ->label('Akun Kredit')
                            ->options(fn () => Account::detail()->active()->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->helperText('Akun yang di-kredit saat pajak dijurnal'),
                    ]),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Toggle::make('is_default')
                            ->label('Default')
                            ->helperText('Akan otomatis dipilih saat transaksi baru')
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
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ppn' => 'primary',
                        'pph' => 'danger',
                        'other' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => TaxRule::types()[$state] ?? $state),
                Tables\Columns\TextColumn::make('module')
                    ->label('Modul')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => TaxRule::modules()[$state] ?? $state),
                Tables\Columns\TextColumn::make('method')
                    ->label('Metode')
                    ->formatStateUsing(fn (string $state): string => TaxRule::methods()[$state] ?? $state)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('rate')
                    ->label('Tarif')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray'),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktif'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Jenis')
                    ->options(TaxRule::types()),
                Tables\Filters\SelectFilter::make('module')
                    ->label('Modul')
                    ->options(TaxRule::modules()),
                Tables\Filters\TernaryFilter::make('is_default')
                    ->label('Default')
                    ->trueLabel('Ya')
                    ->falseLabel('Tidak'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListTaxRules::route('/'),
            'create' => Pages\CreateTaxRule::route('/create'),
            'edit' => Pages\EditTaxRule::route('/{record}/edit'),
        ];
    }
}
