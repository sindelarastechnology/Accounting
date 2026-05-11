<?php

namespace App\Filament\Resources\PurchaseResource\Pages;

use App\Filament\Resources\PurchaseResource;
use App\Models\Period;
use App\Models\TaxRule;
use App\Services\PurchaseService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchase extends CreateRecord
{
    protected static string $resource = PurchaseResource::class;

    protected function handleRecordCreation(array $data): \App\Models\Purchase
    {
        $items = $data['items'] ?? [];
        $taxIds = $data['tax_ids'] ?? [];

        $itemsData = [];
        foreach ($items as $item) {
            $qty = (float) ($item['qty'] ?? 1);
            $price = (float) ($item['unit_price'] ?? 0);
            $pct = (float) ($item['discount_percent'] ?? 0);
            $discountAmount = $qty * $price * $pct / 100;

            $itemsData[] = [
                'product_id' => $item['product_id'] ?? null,
                'description' => $item['description'] ?? '',
                'qty' => $qty,
                'unit' => $item['unit'] ?? 'pcs',
                'unit_price' => $price,
                'discount_percent' => $pct,
                'discount_amount' => $discountAmount,
                'account_id' => $item['account_id'] ?? null,
            ];
        }

        $taxRules = [];
        if (is_array($taxIds)) {
            foreach ($taxIds as $taxId) {
                $taxRules[] = ['tax_rule_id' => $taxId, 'method' => 'exclusive'];
            }
        }

        return PurchaseService::createPurchase($data, $itemsData, $taxRules);
    }
}
