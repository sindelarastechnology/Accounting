<?php

namespace App\Filament\Resources\DebitNoteResource\Pages;

use App\Filament\Resources\DebitNoteResource;
use App\Models\DebitNoteItem;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;

class ViewDebitNote extends ViewRecord
{
    protected static string $resource = DebitNoteResource::class;

    public function form(Form $form): Form
    {
        $debitNote = $this->record->load(['items', 'contact', 'purchase']);

        $subtotal = (float) $debitNote->subtotal;
        $taxAmount = (float) $debitNote->tax_amount;
        $total = (float) $debitNote->total;

        return $form->schema([
            Forms\Components\Tabs::make('Debit Note Detail')
                ->tabs([
                    Forms\Components\Tabs\Tab::make('Informasi Debit Note')
                        ->schema([
                            Forms\Components\Placeholder::make('number')
                                ->label('No. Debit Note')
                                ->content($debitNote->number ?? '-'),
                            Forms\Components\Placeholder::make('contact')
                                ->label('Supplier')
                                ->content($debitNote->contact->name ?? '-'),
                            Forms\Components\Placeholder::make('purchase')
                                ->label('Pembelian Terkait')
                                ->content($debitNote->purchase ? $debitNote->purchase->number : '-'),
                            Forms\Components\Placeholder::make('date')
                                ->label('Tanggal')
                                ->content($debitNote->date ? $debitNote->date->format('d/m/Y') : '-'),
                            Forms\Components\Placeholder::make('status')
                                ->label('Status')
                                ->content(fn() => match($debitNote->status) {
                                    'draft' => 'Draft',
                                    'posted' => 'Posted',
                                    'applied' => 'Applied',
                                    'cancelled' => 'Dibatalkan',
                                    default => ucfirst($debitNote->status),
                                }),
                            Forms\Components\Placeholder::make('reason')
                                ->label('Alasan')
                                ->content(new HtmlString(nl2br(e($debitNote->reason ?? '-')))),
                        ])->columns(2),

                    Forms\Components\Tabs\Tab::make('Item Retur')
                        ->schema([
                            ...collect($debitNote->items)->map(fn($item) => Forms\Components\Section::make($item->description)
                                ->schema([
                                    Forms\Components\Placeholder::make('qty_' . $item->id)
                                        ->label('Qty')
                                        ->content($item->qty . ' ' . ($item->unit ?? 'pcs')),
                                    Forms\Components\Placeholder::make('price_' . $item->id)
                                        ->label('Harga Satuan')
                                        ->content(rupiah($item->unit_price)),
                                    Forms\Components\Placeholder::make('discount_' . $item->id)
                                        ->label('Diskon')
                                        ->content($item->discount_percent > 0 ? $item->discount_percent . '%' : '-'),
                                    Forms\Components\Placeholder::make('subtotal_' . $item->id)
                                        ->label('Subtotal')
                                        ->content(rupiah($item->subtotal))
                                        ->weight('bold'),
                                ])->columns(4)
                            )->toArray(),
                        ]),

                    Forms\Components\Tabs\Tab::make('Ringkasan')
                        ->schema([
                            Forms\Components\Placeholder::make('subtotal')
                                ->label('Subtotal')
                                ->content(rupiah($subtotal)),
                            Forms\Components\Placeholder::make('tax_amount')
                                ->label('Pajak')
                                ->content(rupiah($taxAmount)),
                            Forms\Components\Placeholder::make('divider')
                                ->label('')
                                ->content(new HtmlString('<hr style="border: 1px solid #e5e7eb; margin: 8px 0;">')),
                            Forms\Components\Placeholder::make('total')
                                ->label('TOTAL')
                                ->content(new HtmlString('<span style="font-size: 1.3em; font-weight: bold;">' . rupiah($total) . '</span>')),
                            Forms\Components\Placeholder::make('applied_amount')
                                ->label('Terapkan')
                                ->content(rupiah($debitNote->applied_amount)),
                            Forms\Components\Placeholder::make('remaining_amount')
                                ->label('Sisa')
                                ->content(rupiah($debitNote->remaining_amount)),
                        ])->columns(2),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => $this->record->status === 'draft'),

            Actions\Action::make('post')
                ->label('Posting')
                ->color('primary')
                ->icon('heroicon-o-check-circle')
                ->visible(fn () => $this->record->status === 'draft')
                ->requiresConfirmation()
                ->modalHeading('Posting Debit Note')
                ->modalDescription('Debit Note akan diposting dan jurnal akuntansi akan dibuat. Lanjutkan?')
                ->modalSubmitActionLabel('Posting')
                ->action(function () {
                    try {
                        \App\Services\DebitNoteService::postDebitNote($this->record);
                        Notification::make()
                            ->title('Debit Note berhasil diposting')
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

            Actions\Action::make('apply')
                ->label('Terapkan ke Pembelian')
                ->color('success')
                ->icon('heroicon-o-arrow-right-circle')
                ->visible(fn () => $this->record->status === 'posted' && $this->record->remaining_amount > 0)
                ->form([
                    Forms\Components\Select::make('purchase_id')
                        ->label('Pilih Pembelian')
                        ->options(function () {
                            return \App\Models\Purchase::where('contact_id', $this->record->contact_id)
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
                ->action(function (array $data) {
                    try {
                        $purchase = \App\Models\Purchase::findOrFail($data['purchase_id']);
                        \App\Services\DebitNoteService::applyDebitNoteToPurchase($this->record, $purchase);
                        Notification::make()
                            ->title('Debit Note berhasil diterapkan ke pembelian')
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
                ->visible(fn () => !in_array($this->record->status, ['applied', 'cancelled']))
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
                ->action(function (array $data) {
                    try {
                        \App\Services\DebitNoteService::cancelDebitNote($this->record);
                        Notification::make()
                            ->title('Debit Note berhasil dibatalkan')
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
        ];
    }
}
