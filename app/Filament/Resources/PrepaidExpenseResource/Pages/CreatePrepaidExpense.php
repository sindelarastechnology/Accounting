<?php

namespace App\Filament\Resources\PrepaidExpenseResource\Pages;

use App\Filament\Resources\PrepaidExpenseResource;
use App\Models\Period;
use App\Models\Wallet;
use App\Services\AccrualService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePrepaidExpense extends CreateRecord
{
    protected static string $resource = PrepaidExpenseResource::class;

    protected function handleRecordCreation(array $data): \App\Models\PrepaidExpense
    {
        $period = Period::findOrFail($data['period_id']);
        $wallet = Wallet::findOrFail($data['wallet_id']);

        return AccrualService::createPrepaidExpense(
            $period,
            $data['asset_account_id'],
            $data['expense_account_id'],
            $wallet,
            (float) $data['total_amount'],
            (int) $data['total_months'],
            $data['description'],
            Auth::id()
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Prepaid berhasil dibuat')
            ->body('Jurnal pembayaran dimuka telah dicatat.')
            ->success();
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Buat & Catat Jurnal'),
            $this->getCancelFormAction(),
        ];
    }
}
