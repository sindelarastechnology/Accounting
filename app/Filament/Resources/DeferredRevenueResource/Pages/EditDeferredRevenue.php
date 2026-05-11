<?php

namespace App\Filament\Resources\DeferredRevenueResource\Pages;

use App\Filament\Resources\DeferredRevenueResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDeferredRevenue extends EditRecord
{
    protected static string $resource = DeferredRevenueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
