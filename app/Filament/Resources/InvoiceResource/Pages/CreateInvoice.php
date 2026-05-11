<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Exceptions\PeriodClosedException;
use App\Filament\Resources\InvoiceResource;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Period;
use App\Models\Setting;
use App\Models\TaxRule;
use App\Services\InvoiceService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function handleRecordCreation(array $data): Invoice
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

        return InvoiceService::createInvoice($data, $itemsData, $taxRules);
    }
}
