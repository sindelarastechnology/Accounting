<?php

namespace App\Filament\Resources\PrepaidExpenseResource\Pages;

use App\Filament\Resources\PrepaidExpenseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPrepaidExpense extends EditRecord
{
    protected static string $resource = PrepaidExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
