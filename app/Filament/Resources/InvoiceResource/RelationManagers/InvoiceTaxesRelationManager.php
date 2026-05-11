<?php

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class InvoiceTaxesRelationManager extends RelationManager
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

                Tables\Columns\TextColumn::make('tax_code')
                    ->label('Kode'),

                Tables\Columns\TextColumn::make('method')
                    ->label('Metode')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'exclusive' => 'info',
                        'inclusive' => 'warning',
                        'withholding' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'exclusive' => 'Eksklusif',
                        'inclusive' => 'Inklusif',
                        'withholding' => 'Potongan',
                        default => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('rate')
                    ->label('Rate (%)')
                    ->getStateUsing(fn ($record) => $record->rate . '%'),

                Tables\Columns\TextColumn::make('base_amount')
                    ->label('Dasar Pengenaan')
                    ->getStateUsing(fn ($record) => rupiah($record->base_amount)),

                Tables\Columns\TextColumn::make('tax_amount')
                    ->label('Nilai Pajak')
                    ->getStateUsing(fn ($record) => rupiah($record->tax_amount))
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
