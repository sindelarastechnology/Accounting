<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Exceptions\PeriodClosedException;
use App\Filament\Resources\InvoiceResource;
use App\Models\Period;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);
        $this->record->loadMissing(['items', 'taxes.taxRule', 'contact']);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        $record = $this->record;

        $subtotal = $record->items->sum(fn ($item) =>
            $item->qty * $item->unit_price * (1 - $item->discount_percent / 100)
        );

        $totalDiscount = $record->items->sum(fn ($item) =>
            $item->qty * $item->unit_price * ($item->discount_percent / 100)
        );

        return $infolist
            ->schema([
                Infolists\Components\Tabs::make('Invoice Detail')
                    ->tabs([
                        Infolists\Components\Tabs\Tab::make('Informasi Invoice')
                            ->schema([
                                Infolists\Components\Grid::make(2)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('number')
                                            ->label('No. Invoice'),
                                        Infolists\Components\TextEntry::make('contact.name')
                                            ->label('Customer'),
                                        Infolists\Components\TextEntry::make('date')
                                            ->label('Tanggal')
                                            ->date('d/m/Y'),
                                        Infolists\Components\TextEntry::make('due_date')
                                            ->label('Jatuh Tempo')
                                            ->date('d/m/Y'),
                                        Infolists\Components\TextEntry::make('status')
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
                                                'partially_paid' => 'Sebagian Dibayar',
                                                'paid' => 'Lunas',
                                                'cancelled' => 'Dibatalkan',
                                                default => ucfirst($state),
                                            }),
                                        Infolists\Components\TextEntry::make('ref_number')
                                            ->label('No. Referensi')
                                            ->default('-'),
                                        Infolists\Components\TextEntry::make('notes')
                                            ->label('Catatan')
                                            ->html()
                                            ->formatStateUsing(fn ($state) => $state ? nl2br(e($state)) : '-'),
                                    ]),
                            ]),

                        Infolists\Components\Tabs\Tab::make('Item & Produk')
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('items')
                                    ->schema([
                                        Infolists\Components\Grid::make(['default' => 1, 'md' => 4])
                                            ->schema([
                                                Infolists\Components\TextEntry::make('description')
                                                    ->label('Item')
                                                    ->columnSpan(['default' => 1, 'md' => 4]),
                                                Infolists\Components\TextEntry::make('qty')
                                                    ->label('Qty')
                                                    ->formatStateUsing(fn ($state, $record) => $state . ' ' . ($record->unit ?? 'pcs')),
                                                Infolists\Components\TextEntry::make('unit_price')
                                                    ->label('Harga Satuan')
                                                    ->money('IDR', 0, 'id'),
                                                Infolists\Components\TextEntry::make('discount_percent')
                                                    ->label('Diskon')
                                                    ->formatStateUsing(fn ($state) => $state > 0 ? $state . '%' : '-'),
                                                Infolists\Components\TextEntry::make('line_subtotal')
                                                    ->label('Subtotal')
                                                    ->state(fn ($record) => $record->qty * $record->unit_price * (1 - $record->discount_percent / 100))
                                                    ->money('IDR', 0, 'id'),
                                            ]),
                                    ])
                                    ->contained(false),

                                Infolists\Components\Grid::make(2)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('view_subtotal')
                                            ->label('Subtotal')
                                            ->state($subtotal)
                                            ->money('IDR', 0, 'id'),
                                        Infolists\Components\TextEntry::make('view_discount')
                                            ->label('Total Diskon')
                                            ->state($totalDiscount)
                                            ->money('IDR', 0, 'id'),
                                    ]),
                            ]),

                        Infolists\Components\Tabs\Tab::make('Pajak & Ringkasan')
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('taxes')
                                    ->schema([
                                        Infolists\Components\Grid::make(4)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('taxRule.name')
                                                    ->label('Pajak'),
                                                Infolists\Components\TextEntry::make('taxRule.rate')
                                                    ->label('Rate')
                                                    ->suffix('%'),
                                                Infolists\Components\TextEntry::make('taxRule.method')
                                                    ->label('Metode')
                                                    ->formatStateUsing(fn ($state) => ucfirst($state)),
                                                Infolists\Components\TextEntry::make('amount')
                                                    ->label('Jumlah Pajak')
                                                    ->money('IDR', 0, 'id'),
                                            ]),
                                    ])
                                    ->contained(false),

                                Infolists\Components\Section::make('Ringkasan')
                                    ->schema([
                                        Infolists\Components\Grid::make(2)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('summary_subtotal')
                                                    ->label('Subtotal')
                                                    ->state($subtotal)
                                                    ->money('IDR', 0, 'id'),
                                                Infolists\Components\TextEntry::make('summary_discount')
                                                    ->label('Total Diskon')
                                                    ->state($totalDiscount)
                                                    ->money('IDR', 0, 'id'),
                                                Infolists\Components\TextEntry::make('summary_tax')
                                                    ->label('Total Pajak')
                                                    ->state(fn () => $this->calcTotalTax($subtotal))
                                                    ->money('IDR', 0, 'id'),
                                                Infolists\Components\TextEntry::make('summary_total')
                                                    ->label('TOTAL')
                                                    ->state(fn () => $subtotal - $totalDiscount + $this->calcTotalTax($subtotal))
                                                    ->money('IDR', 0, 'id')
                                                    ->weight('bold')
                                                    ->size('lg'),
                                            ]),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    protected function calcTotalTax(float $subtotal): float
    {
        $totalTax = 0;
        foreach ($this->record->taxes as $tax) {
            $rate = (float) $tax->taxRule->rate;
            $method = $tax->taxRule->method;
            if ($method === 'exclusive') {
                $totalTax += $subtotal * ($rate / 100);
            } elseif ($method === 'inclusive') {
                $totalTax += $subtotal - ($subtotal / (1 + $rate / 100));
            } elseif ($method === 'withholding') {
                $totalTax -= $subtotal * ($rate / 100);
            }
        }
        return $totalTax;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('post')
                ->label('Posting')
                ->color('primary')
                ->icon('heroicon-o-check-circle')
                ->visible(fn () => $this->record->status === 'draft')
                ->requiresConfirmation()
                ->modalHeading('Posting Invoice')
                ->modalDescription('Invoice akan diposting dan jurnal akuntansi akan dibuat. Lanjutkan?')
                ->modalSubmitActionLabel('Posting')
                ->action(function () {
                    try {
                        InvoiceService::postInvoice($this->record);
                        Notification::make()
                            ->title('Invoice berhasil diposting')
                            ->success()
                            ->send();
                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Gagal')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('cancel')
                ->label('Batalkan')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->visible(fn () => !in_array($this->record->status, ['paid', 'cancelled']))
                ->modalHeading('Batalkan Invoice')
                ->modalSubmitActionLabel('Batalkan')
                ->form([
                    Forms\Components\Textarea::make('reason')
                        ->label('Alasan Pembatalan')
                        ->required()
                        ->minLength(10)
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    try {
                        InvoiceService::cancelInvoice($this->record, $data['reason'] ?? null);
                        Notification::make()
                            ->title('Invoice berhasil dibatalkan')
                            ->success()
                            ->send();
                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Gagal')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('addPayment')
                ->label('Tambah Pembayaran')
                ->color('success')
                ->icon('heroicon-o-banknotes')
                ->visible(fn () => in_array($this->record->status, ['posted', 'partially_paid']))
                ->modalHeading('Tambah Pembayaran')
                ->modalSubmitActionLabel('Bayar')
                ->form([
                    Forms\Components\DatePicker::make('payment_date')
                        ->label('Tanggal Bayar')
                        ->default(now())
                        ->required(),
                    Forms\Components\TextInput::make('amount')
                        ->label('Jumlah Bayar (Rp)')
                        ->numeric()
                        ->default(fn () => max(0, (float) $this->record->total - (float) $this->record->paid_amount))
                        ->minValue(1)
                        ->required(),
                    Forms\Components\Select::make('wallet_id')
                        ->label('Dompet / Bank')
                        ->options(fn () => \App\Models\Wallet::active()->pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                    Forms\Components\Textarea::make('notes')
                        ->label('Catatan')
                        ->nullable()
                        ->rows(2),
                ])
                ->action(function (array $data) {
                    try {
                        $period = Period::where('is_closed', false)
                            ->where('start_date', '<=', $data['payment_date'])
                            ->where('end_date', '>=', $data['payment_date'])
                            ->first();
                        if (!$period) {
                            $period = Period::where('is_closed', false)->orderBy('start_date')->first();
                        }
                        if (!$period) {
                            throw new PeriodClosedException('Tidak ada periode aktif.');
                        }
                        $payment = PaymentService::createPayment([
                            'payable_type' => 'invoices',
                            'payable_id' => $this->record->id,
                            'period_id' => $period->id,
                            'wallet_id' => $data['wallet_id'],
                            'date' => $data['payment_date'],
                            'amount' => $data['amount'],
                            'withholding_amount' => 0,
                            'method' => 'cash',
                            'notes' => $data['notes'] ?? null,
                            'status' => 'pending',
                        ]);
                        PaymentService::verifyPayment($payment);
                        Notification::make()
                            ->title('Pembayaran berhasil dicatat')
                            ->success()
                            ->send();
                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Gagal')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('printPdf')
                ->label('Cetak PDF')
                ->color('secondary')
                ->icon('heroicon-o-printer')
                ->visible(fn () => $this->record->status !== 'draft')
                ->action(function () {
                    $invoice = $this->record->load(['items.product', 'taxes', 'contact']);
                    $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $invoice])->setPaper('a4', 'portrait');
                    $safeNumber = str_replace(['/', '\\'], '-', $this->record->number);
                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, "Invoice-{$safeNumber}.pdf");
                }),
        ];
    }
}
