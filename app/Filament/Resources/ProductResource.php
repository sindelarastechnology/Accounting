<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Account;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Produk & Jasa';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Informasi Produk')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('code')
                                        ->label('Kode Produk')
                                        ->required()
                                        ->maxLength(50)
                                        ->unique(ignoreRecord: true),
                                    Forms\Components\TextInput::make('name')
                                        ->label('Nama Produk')
                                        ->required()
                                        ->maxLength(200),
                                ]),
                            Forms\Components\Select::make('type')
                                ->label('Tipe')
                                ->options(Product::types())
                                ->required()
                                ->live()
                                ->afterStateUpdated(fn (Forms\Set $set) => $set('stock_on_hand', 0)),
                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\TextInput::make('unit')
                                        ->label('Satuan')
                                        ->maxLength(30)
                                        ->placeholder('pcs, kg, jam, dll'),
                                    Forms\Components\TextInput::make('purchase_price')
                                        ->label('Harga Beli')
                                        ->required()
                                        ->numeric()
                                        ->default(0)
                                        ->prefix('Rp'),
                                    Forms\Components\TextInput::make('selling_price')
                                        ->label('Harga Jual')
                                        ->required()
                                        ->numeric()
                                        ->default(0)
                                        ->prefix('Rp'),
                                ]),
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('stock_on_hand')
                                        ->label('Stok Awal')
                                        ->required()
                                        ->numeric()
                                        ->default(0)
                                        ->visible(fn (Forms\Get $get) => $get('type') === 'goods'),
                                    Forms\Components\TextInput::make('stock_minimum')
                                        ->label('Stok Minimum')
                                        ->required()
                                        ->numeric()
                                        ->default(0)
                                        ->visible(fn (Forms\Get $get) => $get('type') === 'goods'),
                                ]),
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('tax_rate')
                                        ->label('Tarif Pajak (%)')
                                        ->required()
                                        ->numeric()
                                        ->default(0)
                                        ->suffix('%'),
                                    Forms\Components\Toggle::make('is_active')
                                        ->label('Aktif')
                                        ->default(true)
                                        ->required(),
                                ]),
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('subscription_duration')
                                        ->label('Durasi Langganan')
                                        ->numeric()
                                        ->minValue(1)
                                        ->visible(fn (Forms\Get $get) => $get('type') === 'subscription'),
                                    Forms\Components\Select::make('subscription_unit')
                                        ->label('Unit Durasi')
                                        ->options(Product::subscriptionUnits())
                                        ->visible(fn (Forms\Get $get) => $get('type') === 'subscription'),
                                ]),
                            Forms\Components\Textarea::make('description')
                                ->label('Deskripsi')
                                ->columnSpanFull()
                                ->rows(3),
                        ]),
                    Forms\Components\Wizard\Step::make('Akuntansi')
                        ->icon('heroicon-o-calculator')
                        ->schema([
                            Forms\Components\Select::make('revenue_account_id')
                                ->label('Akun Pendapatan')
                                ->options(fn () => Account::byCategory('revenue')->detail()->active()->pluck('name', 'id'))
                                ->searchable()
                                ->helperText('Akun pendapatan saat produk dijual. Default dari pengaturan.')
                                ->required(),
                            Forms\Components\Select::make('cogs_account_id')
                                ->label('Akun HPP')
                                ->options(fn () => Account::byCategory('cogs')->detail()->active()->pluck('name', 'id'))
                                ->searchable()
                                ->visible(fn (Forms\Get $get) => $get('type') === 'goods')
                                ->helperText('Akun HPP untuk barang (wajib jika tipe = barang)'),
                            Forms\Components\Select::make('inventory_account_id')
                                ->label('Akun Persediaan')
                                ->options(fn () => Account::byCategory('asset')->detail()->active()->pluck('name', 'id'))
                                ->searchable()
                                ->visible(fn (Forms\Get $get) => $get('type') === 'goods')
                                ->helperText('Akun persediaan barang (wajib jika tipe = barang)'),
                            Forms\Components\Select::make('purchase_account_id')
                                ->label('Akun Pembelian')
                                ->options(fn () => Account::byCategory('expense')->detail()->active()->pluck('name', 'id'))
                                ->searchable()
                                ->helperText('Akun beban saat produk dibeli (jasa/non-stok)'),
                        ]),
                ])->columnSpanFull()
                    ->skippable()
                    ->columns(2),
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
                    ->sortable()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->name),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'goods' => 'success',
                        'service' => 'primary',
                        'subscription' => 'warning',
                        'bundle' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => Product::types()[$state] ?? $state),
                Tables\Columns\TextColumn::make('available_stock')
                    ->label('Stok Tersedia')
                    ->getStateUsing(function ($record) {
                        if ($record->type !== 'goods') {
                            return '-';
                        }
                        return number_format((float) $record->stock_on_hand, 0, ',', '.');
                    })
                    ->visible(fn (?Product $record) => $record?->type === 'goods')
                    ->sortable(query: function ($query, string $direction) {
                        $query->orderBy('stock_on_hand', $direction);
                    }),
                Tables\Columns\TextColumn::make('unit')
                    ->label('Satuan')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('purchase_price')
                    ->label('Harga Beli')
                    ->money('IDR', locale: 'id')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('selling_price')
                    ->label('Harga Jual')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_on_hand')
                    ->label('Stok')
                    ->numeric(locale: 'id')
                    ->sortable()
                    ->visible(fn (?Product $record) => $record?->type === 'goods'),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktif'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe')
                    ->options(Product::types()),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
