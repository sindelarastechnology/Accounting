<?php

namespace App\Filament\Resources\DeferredRevenueResource\Pages;

use App\Filament\Resources\DeferredRevenueResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDeferredRevenues extends ListRecords
{
    protected static string $resource = DeferredRevenueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
