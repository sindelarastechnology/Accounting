<?php

namespace App\Filament\Resources\PrepaidExpenseResource\Pages;

use App\Filament\Resources\PrepaidExpenseResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPrepaidExpense extends ViewRecord
{
    protected static string $resource = PrepaidExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
