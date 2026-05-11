<?php

namespace App\Filament\Resources\DeferredRevenueResource\Pages;

use App\Filament\Resources\DeferredRevenueResource;
use App\Models\Period;
use App\Models\Wallet;
use App\Services\AccrualService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateDeferredRevenue extends CreateRecord
{
    protected static string $resource = DeferredRevenueResource::class;

    protected function handleRecordCreation(array $data): \App\Models\DeferredRevenue
    {
        $period = Period::findOrFail($data['period_id']);
        $wallet = Wallet::findOrFail($data['wallet_id']);

        return AccrualService::createDeferredRevenue(
            $period,
            $data['liability_account_id'],
            $data['revenue_account_id'],
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
            ->title('Deferred revenue berhasil dibuat')
            ->body('Jurnal pendapatan diterima dimuka telah dicatat.')
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
