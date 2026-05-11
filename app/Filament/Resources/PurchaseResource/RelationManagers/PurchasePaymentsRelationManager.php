<?php

namespace App\Filament\Resources\PurchaseResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PurchasePaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $recordTitleAttribute = 'number';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('No. Pembayaran')
                    ->searchable(),

                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal Bayar')
                    ->date('d/m/Y'),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah')
                    ->getStateUsing(fn ($record) => rupiah($record->amount)),

                Tables\Columns\TextColumn::make('withholding_amount')
                    ->label('PPh 23')
                    ->getStateUsing(fn ($record) => ($record->withholding_amount > 0 ? rupiah($record->withholding_amount) : '-')),

                Tables\Columns\TextColumn::make('wallet.name')
                    ->label('Dari Rekening'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'verified' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pending',
                        'verified' => 'Terverifikasi',
                        'cancelled' => 'Dibatalkan',
                        default => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(30),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
