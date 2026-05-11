<?php

namespace App\Filament\Resources;

use App\Exceptions\PeriodClosedException;
use App\Filament\Resources\DebitNoteResource\Pages;
use App\Models\Account;
use App\Models\Contact;
use App\Models\DebitNote;
use App\Models\Purchase;
use App\Models\Period;
use App\Models\Product;
use App\Models\TaxRule;
use App\Services\DebitNoteService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class DebitNoteResource extends Resource
{
    protected static ?string $model = DebitNote::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-plus';

    protected static ?string $navigationGroup = 'Pembelian';

    protected static ?string $navigationLabel = 'Debit Note';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'number';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Informasi Debit Note')
                        ->schema([
                            Forms\Components\Select::make('contact_id')
                                ->label('Supplier')
                                ->options(function () {
                                    return Contact::whereIn('type', ['supplier', 'both'])
                                        ->orderBy('name')
                                        ->pluck('name', 'id');
                                })
                                ->searchable()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Forms\Set $set) {
                                    $set('purchase_id', null);
                                }),

                            Forms\Components\Select::make('purchase_id')
                                ->label('Terapkan ke Pembelian')
                                ->options(function (Forms\Get $get) {
                                    $contactId = $get('contact_id');
                                    if (!$contactId) return [];
                                    return Purchase::where('contact_id', $contactId)
                                        ->whereIn('status', ['posted', 'partially_paid'])
                                        ->orderBy('date', 'desc')
                                        ->get()
                                        ->mapWithKeys(function ($purchase) {
                                            $remaining = (float) $purchase->total - (float) $purchase->paid_amount;
                                            return [$purchase->id => $purchase->number . ' (Sisa: ' . rupiah($remaining) . ')'];
                                        });
                                })
                                ->searchable()
                                ->nullable()
                                ->helperText('Opsional. Pilih pembelian untuk menerapkan debit note ini.'),

                            Forms\Components\DatePicker::make('date')
                                ->label('Tanggal Debit Note')
                                ->default(now())
                                ->required(),

                            Forms\Components\Select::make('period_id')
                                ->label('Periode Akuntansi')
                                ->options(function () {
                                    return Period::where('is_closed', false)
                                        ->orderBy('start_date')
                                        ->pluck('name', 'id');
                                })
                                ->searchable()
                                ->default(function () {
                                    return Period::where('is_closed', false)
                                        ->where('start_date', '<=', now())
                                        ->where('end_date', '>=', now())
                                        ->value('id')
                                        ?? Period::where('is_closed', false)->value('id');
                                })
                                ->helperText(function () {
                                    if (!Period::where('is_closed', false)->exists()) {
                                        return new HtmlString('<span style="color: #dc2626; font-weight: bold;">Tidak ada periode aktif. Buat periode terlebih dahulu.</span>');
                                    }
                                    return null;
                                })
                                ->required(),

                            Forms\Components\Textarea::make('reason')
                                ->label('Alasan Retur/Debit Note')
                                ->required()
                                ->rows(3),
                        ])
                        ->columns(2),

                    Forms\Components\Wizard\Step::make('Item Retur')
                        ->schema([
                            Forms\Components\Repeater::make('items')
                                ->label('')
                                ->schema([
                                    Forms\Components\Grid::make(12)
                                        ->schema([
                                            Forms\Components\Select::make('product_id')
                                                ->label('Produk')
                                                ->options(function () {
                                                    return Product::active()
                                                        ->orderBy('name')
                                                        ->pluck('name', 'id');
                                                })
                                                ->searchable()
                                                ->nullable()
                                                ->reactive()
                                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                                    if ($state) {
                                                        $product = Product::find($state);
                                                        if ($product) {
                                                            $set('description', $product->name);
                                                            $set('unit', $product->unit ?? 'pcs');
                                                            $set('unit_price', $product->purchase_price);
                                                            $set('account_id', $product->purchase_account_id);
                                                            $set('cost_price', $product->purchase_price);
                                                        }
                                                    }
                                                })
                                                ->columnSpan(3),

                                            Forms\Components\TextInput::make('description')
                                                ->label('Deskripsi')
                                                ->required()
                                                ->columnSpan(3),

                                            Forms\Components\TextInput::make('qty')
                                                ->label('Qty')
                                                ->numeric()
                                                ->default(1)
                                                ->minValue(0.0001)
                                                ->step(1)
                                                ->reactive()
                                                ->columnSpan(2),

                                            Forms\Components\TextInput::make('unit')
                                                ->label('Satuan')
                                                ->default('pcs')
                                                ->columnSpan(1),

                                            Forms\Components\TextInput::make('unit_price')
                                                ->label('Harga Beli (Rp)')
                                                ->numeric()
                                                ->default(0)
                                                ->minValue(0)
                                                ->step(1000)
                                                ->reactive()
                                                ->columnSpan(3),

                                            Forms\Components\TextInput::make('discount_percent')
                                                ->label('Diskon %')
                                                ->numeric()
                                                ->default(0)
                                                ->minValue(0)
                                                ->maxValue(100)
                                                ->suffix('%')
                                                ->reactive()
                                                ->columnSpan(2),

                                            Forms\Components\Placeholder::make('discount_amount')
                                                ->label('Diskon (Rp)')
                                                ->content(function (Forms\Get $get) {
                                                    $qty = (float) ($get('qty') ?? 1);
                                                    $price = (float) ($get('unit_price') ?? 0);
                                                    $pct = (float) ($get('discount_percent') ?? 0);
                                                    return rupiah($qty * $price * $pct / 100);
                                                })
                                                ->reactive()
                                                ->columnSpan(2),

                                            Forms\Components\Placeholder::make('line_total')
                                                ->label('Subtotal')
                                                ->content(function (Forms\Get $get) {
                                                    $qty = (float) ($get('qty') ?? 1);
                                                    $price = (float) ($get('unit_price') ?? 0);
                                                    $pct = (float) ($get('discount_percent') ?? 0);
                                                    $disc = $qty * $price * $pct / 100;
                                                    return rupiah($qty * $price - $disc);
                                                })
                                                ->reactive()
                                                ->columnSpan(2),

                                            Forms\Components\Select::make('account_id')
                                                ->label('Akun Beban/Persediaan')
                                                ->options(function () {
                                                    return Account::whereIn('category', ['asset', 'cogs', 'expense'])
                                                        ->where('is_header', false)
                                                        ->where('is_active', true)
                                                        ->orderBy('code')
                                                        ->pluck('name', 'id');
                                                })
                                                ->searchable()
                                                ->nullable()
                                                ->columnSpan(6),

                                            Forms\Components\Hidden::make('cost_price')
                                                ->default(0),
                                        ]),
                                ])
                                ->minItems(1)
                                ->defaultItems(1)
                                ->addActionLabel('Tambah Item')
                                ->columnSpanFull(),

                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\Placeholder::make('summary_subtotal')
                                        ->label('Subtotal')
                                        ->content(function (Forms\Get $get) {
                                            $items = $get('items') ?? [];
                                            $total = 0;
                                            foreach ($items as $item) {
                                                $qty = (float) ($item['qty'] ?? 1);
                                                $price = (float) ($item['unit_price'] ?? 0);
                                                $pct = (float) ($item['discount_percent'] ?? 0);
                                                $total += $qty * $price - ($qty * $price * $pct / 100);
                                            }
                                            return rupiah($total);
                                        })
                                        ->reactive(),

                                    Forms\Components\Placeholder::make('summary_discount')
                                        ->label('Total Diskon')
                                        ->content(function (Forms\Get $get) {
                                            $items = $get('items') ?? [];
                                            $total = 0;
                                            foreach ($items as $item) {
                                                $qty = (float) ($item['qty'] ?? 1);
                                                $price = (float) ($item['unit_price'] ?? 0);
                                                $pct = (float) ($item['discount_percent'] ?? 0);
                                                $total += $qty * $price * $pct / 100;
                                            }
                                            return rupiah($total);
                                        })
                                        ->reactive(),
                                ])
                                ->columnSpanFull(),
                        ]),

                    Forms\Components\Wizard\Step::make('Pajak & Ringkasan')
                        ->schema([
                            Forms\Components\CheckboxList::make('tax_ids')
                                ->label('Pilih Pajak')
                                ->options(function () {
                                    return TaxRule::active()
                                        ->whereIn('module', ['purchase', 'both'])
                                        ->get()
                                        ->mapWithKeys(function ($rule) {
                                            return [$rule->id => "{$rule->name} ({$rule->rate}%)"];
                                        });
                                })
                                ->descriptions(function () {
                                    return TaxRule::active()
                                        ->whereIn('module', ['purchase', 'both'])
                                        ->get()
                                        ->mapWithKeys(function ($rule) {
                                            return [$rule->id => "Metode: {$rule->method}"];
                                        });
                                })
                                ->columns(2)
                                ->reactive(),

                            Forms\Components\Section::make('Ringkasan Debit Note')
                                ->schema([
                                    Forms\Components\Placeholder::make('summary_subtotal')
                                        ->label('Subtotal')
                                        ->content(function (Forms\Get $get) {
                                            return rupiah(self::computeSubtotal($get('items')));
                                        })
                                        ->reactive(),

                                    Forms\Components\Placeholder::make('summary_discount')
                                        ->label('Total Diskon Item')
                                        ->content(function (Forms\Get $get) {
                                            return rupiah(self::computeDiscount($get('items')));
                                        })
                                        ->reactive(),

                                    Forms\Components\TextInput::make('discount_amount')
                                        ->label('Diskon Tambahan (Header)')
                                        ->numeric()
                                        ->default(0)
                                        ->minValue(0)
                                        ->prefix('Rp')
                                        ->reactive(),

                                    Forms\Components\Placeholder::make('summary_tax')
                                        ->label('Pajak')
                                        ->content(function (Forms\Get $get) {
                                            return rupiah(self::computeTax($get('items'), $get('tax_ids')));
                                        })
                                        ->reactive(),

                                    Forms\Components\Placeholder::make('divider')
                                        ->label('')
                                        ->content(new HtmlString('<hr style="border: 1px solid #e5e7eb; margin: 8px 0;">')),

                                    Forms\Components\Placeholder::make('summary_total')
                                        ->label('TOTAL')
                                        ->content(function (Forms\Get $get) {
                                            $subtotal = self::computeSubtotal($get('items'));
                                            $discount = self::computeDiscount($get('items'));
                                            $headerDiscount = (float) ($get('discount_amount') ?? 0);
                                            $tax = self::computeTax($get('items'), $get('tax_ids'));
                                            return new HtmlString('<span style="font-size: 1.3em; font-weight: bold;">' . rupiah($subtotal - $discount - $headerDiscount + $tax) . '</span>');
                                        })
                                        ->reactive(),
                                ])
                                ->columnSpanFull(),
                        ]),
                ])
                    ->columnSpanFull()
                    ->skippable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('No. Debit Note')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('contact.name')
                    ->label('Supplier')
                    ->searchable()
                    ->limit(25)
                    ->tooltip(fn (DebitNote $record) => $record->contact->name),

                Tables\Columns\TextColumn::make('purchase.number')
                    ->label('Pembelian Terkait')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->getStateUsing(fn (DebitNote $record) => rupiah($record->total))
                    ->sortable(query: function (Builder $query, string $direction) {
                        return $query->orderBy('total', $direction);
                    }),

                Tables\Columns\TextColumn::make('applied_amount')
                    ->label('Terapkan')
                    ->getStateUsing(fn (DebitNote $record) => rupiah($record->applied_amount)),

                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('Sisa')
                    ->getStateUsing(fn (DebitNote $record) => rupiah($record->remaining_amount)),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'posted' => 'info',
                        'applied' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'posted' => 'Posted',
                        'applied' => 'Applied',
                        'cancelled' => 'Dibatalkan',
                        default => ucfirst($state),
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'posted' => 'Posted',
                        'applied' => 'Applied',
                        'cancelled' => 'Dibatalkan',
                    ]),

                Tables\Filters\SelectFilter::make('contact')
                    ->label('Supplier')
                    ->relationship('contact', 'name')
                    ->searchable(),

                Tables\Filters\SelectFilter::make('period')
                    ->label('Periode')
                    ->relationship('period', 'name')
                    ->searchable(),

                Tables\Filters\Filter::make('date')
                    ->label('Tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('date_to')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['date_from'], fn ($q, $d) => $q->where('date', '>=', $d))
                            ->when($data['date_to'], fn ($q, $d) => $q->where('date', '<=', $d));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make()
                    ->visible(fn (DebitNote $record) => $record->status === 'draft'),

                Tables\Actions\Action::make('post')
                    ->label('Posting')
                    ->color('primary')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (DebitNote $record) => $record->status === 'draft')
                    ->requiresConfirmation()
                    ->modalHeading('Posting Debit Note')
                    ->modalDescription('Debit Note akan diposting dan jurnal akuntansi akan dibuat. Lanjutkan?')
                    ->modalSubmitActionLabel('Posting')
                    ->action(function (DebitNote $record) {
                        try {
                            DebitNoteService::postDebitNote($record);
                            Notification::make()
                                ->title('Debit Note berhasil diposting')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('apply')
                    ->label('Terapkan ke Pembelian')
                    ->color('success')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->visible(fn (DebitNote $record) => $record->status === 'posted' && $record->remaining_amount > 0)
                    ->form([
                        Forms\Components\Select::make('purchase_id')
                            ->label('Pilih Pembelian')
                            ->options(function (DebitNote $record) {
                                return Purchase::where('contact_id', $record->contact_id)
                                    ->whereIn('status', ['posted', 'partially_paid'])
                                    ->orderBy('date', 'desc')
                                    ->get()
                                    ->mapWithKeys(function ($purchase) {
                                        $remaining = (float) $purchase->total - (float) $purchase->paid_amount;
                                        return [$purchase->id => $purchase->number . ' (Sisa: ' . rupiah($remaining) . ')'];
                                    });
                            })
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (DebitNote $record, array $data) {
                        try {
                            $purchase = Purchase::findOrFail($data['purchase_id']);
                            DebitNoteService::applyDebitNoteToPurchase($record, $purchase);
                            Notification::make()
                                ->title('Debit Note berhasil diterapkan ke pembelian')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('cancel')
                    ->label('Batalkan')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn (DebitNote $record) => !in_array($record->status, ['applied', 'cancelled']))
                    ->modalHeading('Batalkan Debit Note')
                    ->modalSubmitActionLabel('Batalkan')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Alasan Pembatalan')
                            ->required()
                            ->minLength(10)
                            ->rows(3)
                            ->placeholder('Jelaskan alasan pembatalan (min. 10 karakter)'),
                    ])
                    ->action(function (DebitNote $record, array $data) {
                        try {
                            DebitNoteService::cancelDebitNote($record);
                            Notification::make()
                                ->title('Debit Note berhasil dibatalkan')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
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
            'index' => Pages\ListDebitNotes::route('/'),
            'create' => Pages\CreateDebitNote::route('/create'),
            'view' => Pages\ViewDebitNote::route('/{record}'),
        ];
    }

    private static function computeSubtotal(?array $items): float
    {
        if (!$items) return 0;
        $total = 0;
        foreach ($items as $item) {
            $qty = (float) ($item['qty'] ?? 1);
            $price = (float) ($item['unit_price'] ?? 0);
            $pct = (float) ($item['discount_percent'] ?? 0);
            $total += $qty * $price - ($qty * $price * $pct / 100);
        }
        return $total;
    }

    private static function computeDiscount(?array $items): float
    {
        if (!$items) return 0;
        $total = 0;
        foreach ($items as $item) {
            $qty = (float) ($item['qty'] ?? 1);
            $price = (float) ($item['unit_price'] ?? 0);
            $pct = (float) ($item['discount_percent'] ?? 0);
            $total += $qty * $price * $pct / 100;
        }
        return $total;
    }

    private static function computeTax(?array $items, ?array $taxIds): float
    {
        if (!$items || !$taxIds || !is_array($taxIds)) return 0;
        $subtotal = self::computeSubtotal($items) - self::computeDiscount($items);
        $tax = 0;
        foreach ($taxIds as $taxId) {
            $rule = TaxRule::find($taxId);
            if ($rule) {
                $rate = (float) $rule->rate;
                if ($rule->method === 'exclusive') {
                    $tax += $subtotal * ($rate / 100);
                } elseif ($rule->method === 'inclusive') {
                    $tax += $subtotal - ($subtotal / (1 + $rate / 100));
                } elseif ($rule->method === 'withholding') {
                    $tax -= $subtotal * ($rate / 100);
                }
            }
        }
        return $tax;
    }
}
