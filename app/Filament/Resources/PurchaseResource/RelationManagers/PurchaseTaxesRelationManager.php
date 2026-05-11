<?php

namespace App\Filament\Resources\PurchaseResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PurchaseTaxesRelationManager extends RelationManager
{
    protected static string $relationship = 'taxes';

    protected static ?string $recordTitleAttribute = 'tax_name';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tax_name')
                    ->label('Nama Pajak')
                    ->searchable(),

                Tables\Columns\TextColumn::make('rate')
                    ->label('Rate (%)')
                    ->getStateUsing(fn ($record) => $record->rate . '%'),

                Tables\Columns\TextColumn::make('base_amount')
                    ->label('Dasar Pengenaan')
                    ->getStateUsing(fn ($record) => rupiah($record->base_amount)),

                Tables\Columns\TextColumn::make('tax_amount')
                    ->label('Jumlah Pajak')
                    ->getStateUsing(fn ($record) => rupiah($record->tax_amount))
                    ->weight('bold'),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
