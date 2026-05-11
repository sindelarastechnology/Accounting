<?php

namespace App\Filament\Resources;

use App\Models\Contact;
use App\Models\Payment;
use App\Models\Wallet;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupplierPaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Pembelian';

    protected static ?string $navigationLabel = 'Pembayaran ke Supplier';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'number';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('payable_type', 'purchases')
            ->with(['payable.contact', 'wallet']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('No. Pembayaran')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('payable.number')
                    ->label('No. PO')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('payable.contact.name')
                    ->label('Supplier')
                    ->searchable()
                    ->limit(25)
                    ->tooltip(fn ($record) => $record->payable && $record->payable->contact ? $record->payable->contact->name : ''),

                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal Bayar')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah')
                    ->getStateUsing(fn ($record) => rupiah($record->amount))
                    ->sortable(),

                Tables\Columns\TextColumn::make('withholding_amount')
                    ->label('PPh 23 Dipotong')
                    ->getStateUsing(fn ($record) => ($record->withholding_amount > 0 ? rupiah($record->withholding_amount) : '-')),

                Tables\Columns\TextColumn::make('net_amount')
                    ->label('Dibayar Neto')
                    ->getStateUsing(fn ($record) => rupiah((float) $record->amount - (float) $record->withholding_amount)),

                Tables\Columns\TextColumn::make('wallet.name')
                    ->label('Dari Rekening')
                    ->searchable(),

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
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->notes),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'verified' => 'Terverifikasi',
                        'cancelled' => 'Dibatalkan',
                    ]),

                Tables\Filters\SelectFilter::make('wallet')
                    ->label('Rekening')
                    ->relationship('wallet', 'name')
                    ->searchable(),

                Tables\Filters\SelectFilter::make('contact')
                    ->label('Supplier')
                    ->options(function () {
                        return Contact::whereIn('type', ['supplier', 'both'])
                            ->orderBy('name')
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->query(function (Builder $query, $data) {
                        if ($data['value']) {
                            return $query->whereHas('payable', fn ($q) => $q->where('contact_id', $data['value']));
                        }
                        return $query;
                    }),

                Tables\Filters\Filter::make('date')
                    ->label('Tanggal')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('date_from')
                            ->label('Dari Tanggal'),
                        \Filament\Forms\Components\DatePicker::make('date_to')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['date_from'], fn ($q, $d) => $q->where('date', '>=', $d))
                            ->when($data['date_to'], fn ($q, $d) => $q->where('date', '<=', $d));
                    }),
            ])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\SupplierPaymentResource\Pages\ListSupplierPayments::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
