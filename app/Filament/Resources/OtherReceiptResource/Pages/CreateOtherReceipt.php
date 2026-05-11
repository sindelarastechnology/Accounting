<?php

namespace App\Filament\Resources\OtherReceiptResource\Pages;

use App\Exceptions\PeriodClosedException;
use App\Filament\Resources\OtherReceiptResource;
use App\Models\OtherReceipt;
use App\Services\OtherReceiptService;
use Filament\Resources\Pages\CreateRecord;

class CreateOtherReceipt extends CreateRecord
{
    protected static string $resource = OtherReceiptResource::class;

    protected function handleRecordCreation(array $data): OtherReceipt
    {
        return OtherReceiptService::createReceipt($data);
    }
}
