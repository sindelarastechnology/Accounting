<?php

namespace App\Filament\Resources\OtherReceiptResource\Pages;

use App\Filament\Resources\OtherReceiptResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOtherReceipts extends ListRecords
{
    protected static string $resource = OtherReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
