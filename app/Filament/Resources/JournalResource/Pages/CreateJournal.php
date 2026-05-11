<?php

namespace App\Filament\Resources\JournalResource\Pages;

use App\Exceptions\AccountingImbalanceException;
use App\Exceptions\InvalidAccountException;
use App\Exceptions\PeriodClosedException;
use App\Filament\Resources\JournalResource;
use App\Services\JournalService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateJournal extends CreateRecord
{
    protected static string $resource = JournalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        $data['source'] = 'manual';
        $data['type'] = 'normal';

        return $data;
    }

    protected function afterCreate(): void
    {
        // already handled by overriding handleRecordCreation
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $lines = $data['lines'] ?? [];

        $journalData = [
            'date' => $data['date'],
            'period_id' => $data['period_id'],
            'description' => $data['description'],
            'source' => 'manual',
            'type' => 'normal',
            'created_by' => Auth::id(),
        ];

        try {
            $journal = JournalService::createJournal($journalData, $lines);

            Notification::make()
                ->title('Jurnal berhasil dibuat')
                ->body("Nomor: {$journal->number}")
                ->success()
                ->send();

            return $journal;
        } catch (AccountingImbalanceException $e) {
            Notification::make()
                ->title('Jurnal tidak balance')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->halt();
        } catch (PeriodClosedException $e) {
            Notification::make()
                ->title('Periode ditutup')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->halt();
        } catch (InvalidAccountException $e) {
            Notification::make()
                ->title('Akun tidak valid')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->halt();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal membuat jurnal')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->halt();
        }

        return parent::handleRecordCreation($data);
    }
}
