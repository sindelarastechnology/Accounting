<?php

namespace App\Filament\Resources;

use App\Exceptions\InvalidAccountException;
use App\Exceptions\PeriodClosedException;
use App\Filament\Resources\PurchaseResource\Pages;
use App\Filament\Resources\PurchaseResource\RelationManagers;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Payment;
use App\Models\Period;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Setting;
use App\Models\TaxRule;
use App\Models\Wallet;
use App\Services\PaymentService;
use App\Services\PurchaseService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class PurchaseResource extends Resource
{
    protected static ?string $model = Purchase::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'Pembelian';

    protected static ?string $navigationLabel = 'Pembelian';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'number';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Informasi Pembelian')
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
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('date')
                                    ->label('Tanggal PO')
                                    ->default(now())
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set, $state) {
                                        if ($state) {
                                            $set('due_date', Carbon::parse($state)->addDays(30)->format('Y-m-d'));
                                        }
                                    }),

                                Forms\Components\DatePicker::make('due_date')
                                    ->label('Jatuh Tempo')
                                    ->default(now()->addDays(30))
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

                                Forms\Components\TextInput::make('supplier_invoice_number')
                                    ->label('No. Faktur Supplier')
                                    ->maxLength(50)
                                    ->nullable(),
                            ]),

                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->nullable()
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                    Forms\Components\Wizard\Step::make('Item & Produk')
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
                                                ->label('Akun Pembelian')
                                                ->options(function () {
                                                    return Account::whereIn('category', ['asset', 'cogs'])
                                                        ->where('is_header', false)
                                                        ->where('is_active', true)
                                                        ->orderBy('code')
                                                        ->pluck('name', 'id');
                                                })
                                                ->searchable()
                                                ->nullable()
                                                ->helperText('Untuk barang dagang pilih akun Persediaan, untuk beban langsung pilih akun HPP/Beban.')
                                                ->columnSpan(6),
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

                            Forms\Components\Section::make('Ringkasan Pembelian')
                                ->schema([
                                    Forms\Components\Placeholder::make('summary_subtotal')
                                        ->label('Subtotal')
                                        ->content(function (Forms\Get $get) {
                                            return rupiah(self::computeSubtotal($get('items')));
                                        })
                                        ->reactive(),

                                    Forms\Components\Placeholder::make('summary_discount')
                                        ->label('Total Diskon')
                                        ->content(function (Forms\Get $get) {
                                            return rupiah(self::computeDiscount($get('items')));
                                        })
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
                                            $tax = self::computeTax($get('items'), $get('tax_ids'));
                                            return new HtmlString('<span style="font-size: 1.3em; font-weight: bold;">' . rupiah($subtotal - $discount + $tax) . '</span>');
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
                    ->label('No. PO')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('contact.name')
                    ->label('Supplier')
                    ->searchable()
                    ->limit(25)
                    ->tooltip(fn (Purchase $record) => $record->contact ? $record->contact->name : ''),

                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(function (Purchase $record) {
                        if ($record->due_date && $record->due_date->isPast() && !in_array($record->status, ['paid', 'cancelled'])) {
                            return 'danger';
                        }
                        return null;
                    }),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->getStateUsing(fn (Purchase $record) => rupiah($record->total))
                    ->sortable(query: function (Builder $query, string $direction) {
                        return $query->orderBy('total', $direction);
                    }),

                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('Terbayar')
                    ->getStateUsing(fn (Purchase $record) => rupiah($record->paid_amount)),

                Tables\Columns\TextColumn::make('due_amount')
                    ->label('Sisa Hutang')
                    ->getStateUsing(fn (Purchase $record) => rupiah(max(0, (float) $record->total - (float) $record->paid_amount)))
                    ->sortable(query: function (Builder $query, string $direction) {
                        return $query->orderByRaw('(total - paid_amount) ' . $direction);
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'posted' => 'info',
                        'partially_paid' => 'warning',
                        'paid' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'posted' => 'Posted',
                        'partially_paid' => 'Sebagian',
                        'paid' => 'Lunas',
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
                        'partially_paid' => 'Sebagian',
                        'paid' => 'Lunas',
                        'cancelled' => 'Dibatalkan',
                    ]),

                Tables\Filters\SelectFilter::make('contact')
                    ->label('Supplier')
                    ->relationship('contact', 'name')
                    ->searchable()
                    ->query(function (Builder $query, $data) {
                        if ($data['value']) {
                            return $query->whereHas('contact', function ($q) {
                                $q->whereIn('type', ['supplier', 'both']);
                            });
                        }
                        return $query;
                    }),

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

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make()
                    ->visible(fn (Purchase $record) => $record->status === 'draft'),

                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),

                Tables\Actions\Action::make('post')
                    ->label('Posting')
                    ->color('primary')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (Purchase $record) => $record->status === 'draft')
                    ->requiresConfirmation()
                    ->modalHeading('Posting Purchase Order')
                    ->modalDescription('Purchase Order akan diposting dan jurnal akuntansi akan dibuat otomatis (Debit Persediaan/Beban, Kredit Hutang Usaha). Lanjutkan?')
                    ->modalSubmitActionLabel('Posting')
                    ->action(function (Purchase $record) {
                        try {
                            PurchaseService::postPurchase($record);
                            Notification::make()
                                ->title('Pembelian berhasil diposting')
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
                    ->visible(fn (Purchase $record) => !in_array($record->status, ['paid', 'cancelled']))
                    ->modalHeading('Batalkan Pembelian')
                    ->modalSubmitActionLabel('Batalkan')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Alasan Pembatalan')
                            ->required()
                            ->minLength(10)
                            ->rows(3)
                            ->placeholder('Jelaskan alasan pembatalan (min. 10 karakter)'),
                    ])
                    ->action(function (Purchase $record, array $data) {
                        try {
                            PurchaseService::cancelPurchase($record);
                            Notification::make()
                                ->title('Pembelian berhasil dibatalkan')
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

                Tables\Actions\Action::make('addPayment')
                    ->label('Catat Pembayaran')
                    ->color('success')
                    ->icon('heroicon-o-banknotes')
                    ->visible(fn (Purchase $record) => in_array($record->status, ['posted', 'partially_paid']))
                    ->modalHeading('Catat Pembayaran ke Supplier')
                    ->modalSubmitActionLabel('Bayar')
                    ->form([
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Tanggal Bayar')
                            ->default(now())
                            ->required(),

                        Forms\Components\TextInput::make('amount')
                            ->label('Jumlah Bayar (Rp)')
                            ->numeric()
                            ->default(function (Purchase $record) {
                                return max(0, (float) $record->total - (float) $record->paid_amount);
                            })
                            ->minValue(1)
                            ->required(),

                        Forms\Components\Select::make('wallet_id')
                            ->label('Bayar dari Dompet/Bank')
                            ->options(fn () => Wallet::active()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),

                        Forms\Components\Toggle::make('withholding_tax')
                            ->label('Ada Pemotongan PPh 23?')
                            ->default(false)
                            ->reactive(),

                        Forms\Components\TextInput::make('withholding_amount')
                            ->label('Jumlah PPh 23 Dipotong (Rp)')
                            ->numeric()
                            ->minValue(0)
                            ->visible(fn (Forms\Get $get) => $get('withholding_tax') === true)
                            ->helperText('Jumlah yang dipotong supplier dari pembayaran bruto.'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->nullable()
                            ->rows(2),
                    ])
                    ->action(function (Purchase $record, array $data) {
                        try {
                            $period = Period::where('is_closed', false)
                                ->where('start_date', '<=', $data['payment_date'])
                                ->where('end_date', '>=', $data['payment_date'])
                                ->first();

                            if (!$period) {
                                $period = Period::where('is_closed', false)->orderBy('start_date')->first();
                            }

                            if (!$period) {
                                throw new PeriodClosedException('Tidak ada periode aktif untuk pembayaran.');
                            }

                            $payment = PaymentService::createPayment([
                                'payable_type' => 'purchases',
                                'payable_id' => $record->id,
                                'period_id' => $period->id,
                                'wallet_id' => $data['wallet_id'],
                                'date' => $data['payment_date'],
                                'amount' => $data['amount'],
                                'withholding_amount' => $data['withholding_amount'] ?? 0,
                                'method' => 'cash',
                                'notes' => $data['notes'] ?? null,
                                'status' => 'pending',
                            ]);

                            PaymentService::verifyPayment($payment);

                            Notification::make()
                                ->title('Pembayaran berhasil dicatat')
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

                Tables\Actions\Action::make('printPdf')
                    ->label('Cetak PDF')
                    ->color('secondary')
                    ->icon('heroicon-o-printer')
                    ->visible(fn (Purchase $record) => $record->status !== 'draft')
                    ->action(function (Purchase $record) {
                        $purchase = $record->load(['items.product', 'taxes', 'contact']);
                        $pdf = Pdf::loadView('pdf.purchase', [
                            'purchase' => $purchase,
                        ])->setPaper('a4', 'portrait');

                        $safeNumber = str_replace(['/', '\\'], '-', $record->number);
                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->output();
                        }, "PO-{$safeNumber}.pdf");
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PurchaseItemsRelationManager::class,
            RelationManagers\PurchaseTaxesRelationManager::class,
            RelationManagers\PurchasePaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchases::route('/'),
            'create' => Pages\CreatePurchase::route('/create'),
            'view' => Pages\ViewPurchase::route('/{record}'),
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
                    $tax += $subtotal * ($rate / 100);
                }
            }
        }
        return $tax;
    }
}
