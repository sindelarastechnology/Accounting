<?php

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class InvoiceItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $recordTitleAttribute = 'description';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->description),

                Tables\Columns\TextColumn::make('qty')
                    ->label('Qty'),

                Tables\Columns\TextColumn::make('unit')
                    ->label('Satuan'),

                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Harga Satuan')
                    ->getStateUsing(fn ($record) => rupiah($record->unit_price)),

                Tables\Columns\TextColumn::make('discount_percent')
                    ->label('Diskon %')
                    ->getStateUsing(fn ($record) => ($record->discount_percent > 0 ? $record->discount_percent . '%' : '-')),

                Tables\Columns\TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->getStateUsing(fn ($record) => rupiah($record->subtotal))
                    ->weight('bold'),
            ])
            ->headerActions([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ]);
    }
}
