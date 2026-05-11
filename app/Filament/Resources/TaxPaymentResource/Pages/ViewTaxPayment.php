<?php

namespace App\Filament\Resources\TaxPaymentResource\Pages;

use App\Filament\Resources\TaxPaymentResource;
use App\Models\TaxPayment;
use App\Services\TaxPaymentService;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewTaxPayment extends ViewRecord
{
    protected static string $resource = TaxPaymentResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Setoran Pajak')
                    ->schema([
                        Infolists\Components\TextEntry::make('document_number')
                            ->label('No. Dokumen'),
                        Infolists\Components\TextEntry::make('tax_type')
                            ->label('Jenis Pajak')
                            ->formatStateUsing(fn (string $state) => TaxPayment::TAX_TYPES[$state] ?? $state),
                        Infolists\Components\TextEntry::make('period.name')
                            ->label('Periode'),
                        Infolists\Components\TextEntry::make('payment_date')
                            ->label('Tanggal Bayar')
                            ->date('d/m/Y'),
                        Infolists\Components\TextEntry::make('amount')
                            ->label('Jumlah')
                            ->money('IDR', locale: 'id'),
                        Infolists\Components\TextEntry::make('account.name')
                            ->label('Rekening Kas/Bank'),
                        Infolists\Components\TextEntry::make('reference')
                            ->label('Referensi SSP/NTPN')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Catatan')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'draft' => 'warning',
                                'posted' => 'success',
                                'void' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'draft' => 'Draft',
                                'posted' => 'Posted',
                                'void' => 'Void',
                                default => ucfirst($state),
                            }),
                        Infolists\Components\TextEntry::make('journal_id')
                            ->label('ID Jurnal')
                            ->visible(fn ($record) => $record->journal_id !== null),
                    ])->columns(2),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('post')
                ->label('Posting')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->visible(fn ($record) => $record->status === 'draft')
                ->requiresConfirmation()
                ->modalHeading('Posting Setoran Pajak')
                ->modalDescription('Setoran pajak akan diposting dan jurnal akuntansi akan dibuat. Lanjutkan?')
                ->action(function ($record) {
                    try {
                        TaxPaymentService::post($record);
                        Notification::make()
                            ->title('Setoran pajak berhasil diposting')
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

            Actions\Action::make('void')
                ->label('Void')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->visible(fn ($record) => $record->status === 'posted')
                ->requiresConfirmation()
                ->modalHeading('Void Setoran Pajak')
                ->modalDescription('Setoran pajak akan dibatalkan dan jurnal reversal akan dibuat. Lanjutkan?')
                ->action(function ($record) {
                    try {
                        TaxPaymentService::void($record);
                        Notification::make()
                            ->title('Setoran pajak berhasil dibatalkan')
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
        ];
    }
}
