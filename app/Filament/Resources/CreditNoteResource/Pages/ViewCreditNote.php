<?php

namespace App\Filament\Resources\CreditNoteResource\Pages;

use App\Filament\Resources\CreditNoteResource;
use App\Models\CreditNoteItem;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;

class ViewCreditNote extends ViewRecord
{
    protected static string $resource = CreditNoteResource::class;

    public function form(Form $form): Form
    {
        $creditNote = $this->record->load(['items', 'contact', 'invoice']);

        $subtotal = (float) $creditNote->subtotal;
        $taxAmount = (float) $creditNote->tax_amount;
        $total = (float) $creditNote->total;

        return $form->schema([
            Forms\Components\Tabs::make('Credit Note Detail')
                ->tabs([
                    Forms\Components\Tabs\Tab::make('Informasi Credit Note')
                        ->schema([
                            Forms\Components\Placeholder::make('number')
                                ->label('No. Credit Note')
                                ->content($creditNote->number ?? '-'),
                            Forms\Components\Placeholder::make('contact')
                                ->label('Customer')
                                ->content($creditNote->contact->name ?? '-'),
                            Forms\Components\Placeholder::make('invoice')
                                ->label('Invoice Terkait')
                                ->content($creditNote->invoice ? $creditNote->invoice->number : '-'),
                            Forms\Components\Placeholder::make('date')
                                ->label('Tanggal')
                                ->content($creditNote->date ? $creditNote->date->format('d/m/Y') : '-'),
                            Forms\Components\Placeholder::make('status')
                                ->label('Status')
                                ->content(fn() => match($creditNote->status) {
                                    'draft' => 'Draft',
                                    'posted' => 'Posted',
                                    'applied' => 'Applied',
                                    'cancelled' => 'Dibatalkan',
                                    default => ucfirst($creditNote->status),
                                }),
                            Forms\Components\Placeholder::make('reason')
                                ->label('Alasan')
                                ->content(new HtmlString(nl2br(e($creditNote->reason ?? '-')))),
                        ])->columns(2),

                    Forms\Components\Tabs\Tab::make('Item Retur')
                        ->schema([
                            ...collect($creditNote->items)->map(fn($item) => Forms\Components\Section::make($item->description)
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
                                ->content(rupiah($creditNote->applied_amount)),
                            Forms\Components\Placeholder::make('remaining_amount')
                                ->label('Sisa')
                                ->content(rupiah($creditNote->remaining_amount)),
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
                ->modalHeading('Posting Credit Note')
                ->modalDescription('Credit Note akan diposting dan jurnal akuntansi akan dibuat. Lanjutkan?')
                ->modalSubmitActionLabel('Posting')
                ->action(function () {
                    try {
                        \App\Services\CreditNoteService::postCreditNote($this->record);
                        Notification::make()
                            ->title('Credit Note berhasil diposting')
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
                ->label('Terapkan ke Invoice')
                ->color('success')
                ->icon('heroicon-o-arrow-right-circle')
                ->visible(fn () => $this->record->status === 'posted' && $this->record->remaining_amount > 0)
                ->form([
                    Forms\Components\Select::make('invoice_id')
                        ->label('Pilih Invoice')
                        ->options(function () {
                            return \App\Models\Invoice::where('contact_id', $this->record->contact_id)
                                ->whereIn('status', ['posted', 'partially_paid'])
                                ->orderBy('date', 'desc')
                                ->get()
                                ->mapWithKeys(function ($invoice) {
                                    $remaining = (float) $invoice->total - (float) $invoice->paid_amount;
                                    return [$invoice->id => $invoice->number . ' (Sisa: ' . rupiah($remaining) . ')'];
                                });
                        })
                        ->searchable()
                        ->required(),
                ])
                ->action(function (array $data) {
                    try {
                        $invoice = \App\Models\Invoice::findOrFail($data['invoice_id']);
                        \App\Services\CreditNoteService::applyCreditNoteToInvoice($this->record, $invoice);
                        Notification::make()
                            ->title('Credit Note berhasil diterapkan ke invoice')
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
                ->modalHeading('Batalkan Credit Note')
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
                        \App\Services\CreditNoteService::cancelCreditNote($this->record);
                        Notification::make()
                            ->title('Credit Note berhasil dibatalkan')
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
