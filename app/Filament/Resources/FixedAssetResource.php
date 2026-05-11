<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FixedAssetResource\Pages;
use App\Models\Account;
use App\Models\FixedAsset;
use App\Services\FixedAssetService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class FixedAssetResource extends Resource
{
    protected static ?string $model = FixedAsset::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Aset Tetap';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Aset')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Kode Aset')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Aset')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('acquisition_date')
                            ->label('Tanggal Perolehan')
                            ->required(),
                    ])->columns(3),
                Forms\Components\Section::make('Nilai & Penyusutan')
                    ->schema([
                        Forms\Components\TextInput::make('acquisition_cost')
                            ->label('Harga Perolehan')
                            ->required()
                            ->numeric()
                            ->prefix('Rp'),
                        Forms\Components\TextInput::make('salvage_value')
                            ->label('Nilai Sisa')
                            ->numeric()
                            ->default(0)
                            ->prefix('Rp'),
                        Forms\Components\TextInput::make('useful_life_years')
                            ->label('Masa Manfaat (Tahun)')
                            ->required()
                            ->numeric()
                            ->minValue(1),
                        Forms\Components\Select::make('depreciation_method')
                            ->label('Metode Penyusutan')
                            ->options([
                                'straight_line' => 'Garis Lurus',
                                'double_declining' => 'Saldo Menurun Ganda',
                                'sum_of_years' => 'Jumlah Angka Tahun',
                            ])
                            ->default('straight_line')
                            ->required(),
                    ])->columns(3),
                Forms\Components\Section::make('Akun')
                    ->schema([
                        Forms\Components\Select::make('asset_account_id')
                            ->label('Akun Aset Tetap')
                            ->relationship('assetAccount', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('depreciation_account_id')
                            ->label('Akun Beban Penyusutan')
                            ->relationship('depreciationAccount', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('accumulated_depreciation_account_id')
                            ->label('Akun Akumulasi Penyusutan')
                            ->options(
                                Account::where('is_header', false)
                                    ->where('is_active', true)
                                    ->where('code', 'like', '15%')
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->preload()
                            ->helperText('Pilih akun Akumulasi Penyusutan (kode 1510/1520)'),
                    ])->columns(2),
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
                    ->label('Nama Aset')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('acquisition_date')
                    ->label('Tgl Perolehan')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('acquisition_cost')
                    ->label('Harga Perolehan')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('accumulated_depreciation')
                    ->label('Akumulasi Penyusutan')
                    ->money('IDR', locale: 'id'),
                Tables\Columns\TextColumn::make('accumulatedDepreciationAccount.name')
                    ->label('Akun Akumulasi Penyusutan')
                    ->searchable()
                    ->limit(25),
                Tables\Columns\TextColumn::make('book_value')
                    ->label('Nilai Buku')
                    ->getStateUsing(fn (FixedAsset $record) => (float) $record->acquisition_cost - (float) $record->accumulated_depreciation)
                    ->money('IDR', locale: 'id'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'depreciated' => 'info',
                        'disposed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Aktif',
                        'depreciated' => 'Disusutkan Penuh',
                        'disposed' => 'Dihapus',
                        default => $state,
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Aktif',
                        'depreciated' => 'Disusutkan Penuh',
                        'disposed' => 'Dihapus',
                    ]),
                Tables\Filters\SelectFilter::make('depreciation_method')
                    ->options([
                        'straight_line' => 'Garis Lurus',
                        'double_declining' => 'Saldo Menurun Ganda',
                        'sum_of_years' => 'Jumlah Angka Tahun',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('depreciate')
                    ->label('Catat Penyusutan')
                    ->icon('heroicon-o-calculator')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (FixedAsset $record) => $record->status === 'active' && !$record->is_fully_depreciated)
                    ->action(function (FixedAsset $record) {
                        try {
                            FixedAssetService::recordDepreciation($record);
                            Notification::make()
                                ->title('Penyusutan berhasil dicatat')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal mencatat penyusutan')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('dispose')
                    ->label('Hapus Aset')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->form([
                        Forms\Components\DatePicker::make('disposal_date')
                            ->label('Tanggal Penghapusan')
                            ->required(),
                        Forms\Components\TextInput::make('disposal_amount')
                            ->label('Nilai Penghapusan')
                            ->numeric()
                            ->default(0)
                            ->prefix('Rp'),
                    ])
                    ->requiresConfirmation()
                    ->visible(fn (FixedAsset $record) => $record->status !== 'disposed')
                    ->action(function (FixedAsset $record, array $data) {
                        try {
                            FixedAssetService::disposeAsset(
                                $record,
                                $data['disposal_date'],
                                $data['disposal_amount']
                            );
                            Notification::make()
                                ->title('Aset berhasil dihapus')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal menghapus aset')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('depreciate_all')
                    ->label('Catat Penyusutan Semua')
                    ->icon('heroicon-o-calculator')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function () {
                        $results = FixedAssetService::calculateAllMonthlyDepreciation();
                        $success = count($results['success'] ?? []);
                        $failed = count($results['failed'] ?? []);
                        Notification::make()
                            ->title("Berhasil: {$success}, Gagal: {$failed}")
                            ->body($failed > 0 ? implode("\n", array_map(fn ($r) => $r['name'] . ': ' . $r['error'], $results['failed'] ?? [])) : null)
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
            'index' => Pages\ListFixedAssets::route('/'),
            'create' => Pages\CreateFixedAsset::route('/create'),
            'view' => Pages\ViewFixedAsset::route('/{record}'),
            'edit' => Pages\EditFixedAsset::route('/{record}/edit'),
        ];
    }
}
