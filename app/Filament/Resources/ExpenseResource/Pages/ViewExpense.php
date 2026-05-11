<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Filament\Resources\ExpenseResource;
use App\Services\ExpenseService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;

class ViewExpense extends ViewRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function getFormSchema(): array
    {
        $expense = $this->record->load(['account', 'wallet', 'contact']);

        return [
            Forms\Components\Section::make('Detail Beban')
                ->schema([
                    Forms\Components\Placeholder::make('number')
                        ->label('No. Beban')
                        ->content($expense->number ?? '-'),
                    Forms\Components\Placeholder::make('date')
                        ->label('Tanggal')
                        ->content($expense->date ? $expense->date->format('d/m/Y') : '-'),
                    Forms\Components\Placeholder::make('account')
                        ->label('Akun Beban')
                        ->content($expense->account->name ?? '-'),
                    Forms\Components\Placeholder::make('wallet')
                        ->label('Dibayar dari')
                        ->content($expense->wallet->name ?? '-'),
                    Forms\Components\Placeholder::make('amount')
                        ->label('Jumlah')
                        ->content(rupiah($expense->amount)),
                    Forms\Components\Placeholder::make('receipt_number')
                        ->label('No. Bukti')
                        ->content($expense->receipt_number ?? '-'),
                    Forms\Components\Placeholder::make('status')
                        ->label('Status')
                        ->content(fn() => match($expense->status) {
                            'draft' => 'Draft',
                            'posted' => 'Posted',
                            'cancelled' => 'Dibatalkan',
                            default => ucfirst($expense->status),
                        }),
                    Forms\Components\Placeholder::make('name')
                        ->label('Keterangan')
                        ->content(new HtmlString(nl2br(e($expense->name ?? '-')))),
                    Forms\Components\Placeholder::make('notes')
                        ->label('Catatan')
                        ->content(new HtmlString(nl2br(e($expense->notes ?? '-')))),
                ])->columns(2),

            Forms\Components\Section::make('Lampiran')
                ->visible(fn () => $expense->attachment)
                ->schema([
                    Forms\Components\Image::make('attachment')
                        ->label('Bukti Lampiran')
                        ->visible(fn () => $expense->attachment && str_ends_with(strtolower($expense->attachment), '.jpg') || str_ends_with(strtolower($expense->attachment), '.png') || str_ends_with(strtolower($expense->attachment), '.jpeg')),
                    Forms\Components\Placeholder::make('attachment_file')
                        ->label('File Lampiran')
                        ->visible(fn () => $expense->attachment && str_ends_with(strtolower($expense->attachment), '.pdf'))
                        ->content(fn () => new HtmlString('<a href="' . asset('storage/' . $expense->attachment) . '" target="_blank" style="color: #2563eb;">Lihat PDF</a>')),
                ]),
        ];
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
                ->modalHeading('Posting Beban')
                ->modalDescription('Beban akan diposting dan jurnal akuntansi akan dibuat. Lanjutkan?')
                ->modalSubmitActionLabel('Posting')
                ->action(function () {
                    try {
                        ExpenseService::postExpense($this->record);
                        Notification::make()
                            ->title('Beban berhasil diposting')
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
                ->visible(fn () => in_array($this->record->status, ['draft', 'posted']))
                ->modalHeading('Batalkan Beban')
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
                        ExpenseService::cancelExpense($this->record);
                        Notification::make()
                            ->title('Beban berhasil dibatalkan')
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
