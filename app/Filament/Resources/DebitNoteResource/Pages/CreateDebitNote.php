<?php

namespace App\Filament\Resources\DebitNoteResource\Pages;

use App\Filament\Resources\DebitNoteResource;
use App\Models\DebitNote;
use App\Models\TaxRule;
use App\Services\DebitNoteService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateDebitNote extends CreateRecord
{
    protected static string $resource = DebitNoteResource::class;

    protected function handleRecordCreation(array $data): DebitNote
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
                'cost_price' => $item['cost_price'] ?? 0,
            ];
        }

        $taxRules = [];
        if (is_array($taxIds)) {
            foreach ($taxIds as $taxId) {
                $taxRules[] = ['tax_rule_id' => $taxId, 'method' => 'exclusive'];
            }
        }

        return DebitNoteService::createDebitNote($data, $itemsData, $taxRules);
    }
}
