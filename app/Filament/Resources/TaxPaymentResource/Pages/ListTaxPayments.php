<?php

namespace App\Filament\Resources\TaxPaymentResource\Pages;

use App\Filament\Resources\TaxPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTaxPayments extends ListRecords
{
    protected static string $resource = TaxPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
