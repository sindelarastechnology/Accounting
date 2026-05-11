<?php

namespace App\Filament\Resources\CreditNoteResource\Pages;

use App\Filament\Resources\CreditNoteResource;
use App\Models\CreditNote;
use App\Models\TaxRule;
use App\Services\CreditNoteService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateCreditNote extends CreateRecord
{
    protected static string $resource = CreditNoteResource::class;

    protected function handleRecordCreation(array $data): CreditNote
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
                'revenue_account_id' => $item['revenue_account_id'] ?? null,
                'cost_price' => $item['cost_price'] ?? 0,
            ];
        }

        $taxRules = [];
        if (is_array($taxIds)) {
            foreach ($taxIds as $taxId) {
                $taxRules[] = ['tax_rule_id' => $taxId, 'method' => 'exclusive'];
            }
        }

        return CreditNoteService::createCreditNote($data, $itemsData, $taxRules);
    }
}
