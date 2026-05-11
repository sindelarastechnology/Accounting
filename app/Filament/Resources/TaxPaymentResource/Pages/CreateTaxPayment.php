<?php

namespace App\Filament\Resources\TaxPaymentResource\Pages;

use App\Filament\Resources\TaxPaymentResource;
use App\Services\TaxPaymentService;
use Filament\Resources\Pages\CreateRecord;

class CreateTaxPayment extends CreateRecord
{
    protected static string $resource = TaxPaymentResource::class;

    protected function handleRecordCreation(array $data): \App\Models\TaxPayment
    {
        return TaxPaymentService::create($data);
    }
}
