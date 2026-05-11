<?php

namespace App\Filament\Resources\FixedAssetResource\Pages;

use App\Filament\Resources\FixedAssetResource;
use App\Models\FixedAsset;
use App\Services\FixedAssetService;
use Filament\Resources\Pages\CreateRecord;

class CreateFixedAsset extends CreateRecord
{
    protected static string $resource = FixedAssetResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        return $data;
    }

    protected function afterCreate(): void
    {
        $asset = $this->record;
        $monthlyDepreciation = FixedAssetService::calculateMonthlyDepreciation($asset);
        $asset->update(['monthly_depreciation' => $monthlyDepreciation]);
    }
}
