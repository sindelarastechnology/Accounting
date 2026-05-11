<?php

namespace App\Filament\Resources\DeferredRevenueResource\Pages;

use App\Filament\Resources\DeferredRevenueResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDeferredRevenue extends ViewRecord
{
    protected static string $resource = DeferredRevenueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
