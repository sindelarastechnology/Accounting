<?php

namespace App\Filament\Resources\JournalResource\Pages;

use App\Exceptions\InvalidAccountException;
use App\Exceptions\PeriodClosedException;
use App\Filament\Resources\JournalResource;
use App\Services\JournalService;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewJournal extends ViewRecord
{
    protected static string $resource = JournalResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);
        $this->record->loadMissing(['lines.account', 'lines.wallet']);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('void')
                ->label('Void')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->visible(fn () => $this->record->type === 'normal' || $this->record->type === 'opening')
                ->requiresConfirmation()
                ->modalHeading('Void Jurnal')
                ->modalDescription('Jurnal ini akan dibatalkan dengan membuat jurnal reversal.')
                ->modalSubmitActionLabel('Void Jurnal')
                ->form([
                    Forms\Components\Textarea::make('reason')
                        ->label('Alasan Void')
                        ->required()
                        ->minLength(10)
                        ->rows(3)
                        ->placeholder('Jelaskan alasan pembatalan jurnal ini (min. 10 karakter)'),
                ])
                ->action(function (array $data) {
                    try {
                        $reversal = JournalService::voidJournal($this->record, $data['reason']);

                        Notification::make()
                            ->title('Jurnal berhasil di-void')
                            ->body("Jurnal reversal dibuat: {$reversal->number}")
                            ->success()
                            ->send();

                        $this->redirect(JournalResource::getUrl('view', ['record' => $reversal]));
                    } catch (PeriodClosedException $e) {
                        Notification::make()
                            ->title('Gagal')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    } catch (InvalidAccountException $e) {
                        Notification::make()
                            ->title('Gagal')
                            ->body($e->getMessage())
                            ->danger()
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

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Detail Jurnal')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('number')
                                    ->label('No. Jurnal'),
                                Infolists\Components\TextEntry::make('date')
                                    ->label('Tanggal')
                                    ->date('d/m/Y'),
                                Infolists\Components\TextEntry::make('period.name')
                                    ->label('Periode'),
                                Infolists\Components\TextEntry::make('description')
                                    ->label('Deskripsi'),
                            ]),
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('source')
                                    ->label('Sumber')
                                    ->formatStateUsing(fn ($state) => \App\Models\Journal::sources()[$state] ?? $state),
                                Infolists\Components\TextEntry::make('type')
                                    ->label('Tipe')
                                    ->formatStateUsing(fn ($state) => \App\Models\Journal::types()[$state] ?? $state),
                                Infolists\Components\TextEntry::make('createdBy.name')
                                    ->label('Dibuat Oleh'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Baris Jurnal')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('lines')
                            ->schema([
                                Infolists\Components\Grid::make(['default' => 1, 'md' => 6])
                                    ->schema([
                                        Infolists\Components\TextEntry::make('account.code')
                                            ->label('Kode Akun'),
                                        Infolists\Components\TextEntry::make('account.name')
                                            ->label('Nama Akun'),
                                        Infolists\Components\TextEntry::make('debit_amount')
                                            ->label('Debit')
                                            ->money('IDR', 0, 'id'),
                                        Infolists\Components\TextEntry::make('credit_amount')
                                            ->label('Kredit')
                                            ->money('IDR', 0, 'id'),
                                        Infolists\Components\TextEntry::make('wallet.name')
                                            ->label('Dompet'),
                                        Infolists\Components\TextEntry::make('description')
                                            ->label('Keterangan'),
                                    ]),
                            ])
                            ->contained(false),

                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('total_debit')
                                    ->label('Total Debit')
                                    ->state(fn ($record) => $record->lines->sum('debit_amount'))
                                    ->money('IDR', 0, 'id'),
                                Infolists\Components\TextEntry::make('total_credit')
                                    ->label('Total Kredit')
                                    ->state(fn ($record) => $record->lines->sum('credit_amount'))
                                    ->money('IDR', 0, 'id'),
                            ]),
                    ]),
            ]);
    }
}
