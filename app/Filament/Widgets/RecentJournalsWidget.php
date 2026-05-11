<?php

namespace App\Filament\Widgets;

use App\Models\Journal;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentJournalsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Journal::query()->whereNotIn('type', ['void'])->latest()->limit(5))
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('No. Jurnal')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d/m/Y'),
                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(40),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->getStateUsing(fn ($record) => rupiah($record->lines->sum('debit_amount'))),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'normal' => 'success',
                        'reversal' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'normal' => 'Normal',
                        'reversal' => 'Reversal',
                        default => ucfirst($state),
                    }),
            ])
            ->actions([])
            ->paginated(false);
    }
}
