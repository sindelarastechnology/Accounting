<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use App\Models\Wallet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Penjualan';

    protected static ?string $navigationLabel = 'Pembayaran Masuk';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'number';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(fn () => Payment::query()
                ->where('payable_type', 'invoices')
                ->with(['payable.contact', 'wallet']))
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('No. Pembayaran')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('payable.number')
                    ->label('No. Invoice')
                    ->searchable()
                    ->getStateUsing(function (Payment $record) {
                        if ($record->payable_type === 'invoices' && $record->payable) {
                            return $record->payable->number;
                        }
                        return '-';
                    }),

                Tables\Columns\TextColumn::make('contact')
                    ->label('Customer')
                    ->getStateUsing(function (Payment $record) {
                        if ($record->payable_type === 'invoices' && $record->payable && $record->payable->contact) {
                            return $record->payable->contact->name;
                        }
                        return '-';
                    })
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereHas('payable', function ($q) use ($search) {
                            $q->whereHas('contact', function ($cq) use ($search) {
                                $cq->where('name', 'like', "%{$search}%");
                            });
                        });
                    })
                    ->limit(25),

                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal Bayar')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah')
                    ->getStateUsing(fn (Payment $record) => rupiah($record->amount))
                    ->sortable(),

                Tables\Columns\TextColumn::make('wallet.name')
                    ->label('Dompet / Bank')
                    ->searchable()
                    ->limit(20),

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
                        'verified' => 'Verified',
                        'cancelled' => 'Dibatalkan',
                        default => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(30)
                    ->tooltip(fn (Payment $record) => $record->notes),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'verified' => 'Verified',
                        'cancelled' => 'Dibatalkan',
                    ]),

                Tables\Filters\SelectFilter::make('wallet')
                    ->label('Dompet / Bank')
                    ->relationship('wallet', 'name')
                    ->searchable(),

                Tables\Filters\Filter::make('date')
                    ->label('Tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('date_to')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['date_from'], fn ($q, $d) => $q->where('date', '>=', $d))
                            ->when($data['date_to'], fn ($q, $d) => $q->where('date', '<=', $d));
                    }),
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('date', 'desc');
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
            'index' => Pages\ListPayments::route('/'),
        ];
    }
}
