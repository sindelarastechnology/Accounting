<?php

namespace App\Filament\Resources\FundTransferResource\Pages;

use App\Exceptions\PeriodClosedException;
use App\Filament\Resources\FundTransferResource;
use App\Models\FundTransfer;
use App\Services\FundTransferService;
use Filament\Resources\Pages\CreateRecord;

class CreateFundTransfer extends CreateRecord
{
    protected static string $resource = FundTransferResource::class;

    protected function handleRecordCreation(array $data): FundTransfer
    {
        $hasFee = $data['has_fee'] ?? false;
        if (!$hasFee) {
            $data['fee_amount'] = 0;
            $data['fee_account_id'] = null;
        }
        unset($data['has_fee']);

        return FundTransferService::createTransfer($data);
    }
}
