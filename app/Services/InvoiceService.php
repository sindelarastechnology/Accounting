<?php

namespace App\Services;

use App\Exceptions\InvalidAccountException;
use App\Exceptions\PeriodClosedException;
use App\Helpers\AccountResolver;
use App\Models\Account;
use App\Models\Contact;
use App\Models\InventoryMovement;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceTax;
use App\Models\Journal;
use App\Models\Period;
use App\Models\Product;
use App\Models\Setting;
use App\Models\TaxRule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class InvoiceService extends BaseTransactionService
{
    public static function createInvoice(array $data, array $items, array $taxRules = []): Invoice
    {
        if (empty($items)) {
            throw new InvalidAccountException('Invoice harus memiliki minimal 1 item.');
        }

        $contact = Contact::findOrFail($data['contact_id']);
        if (!in_array($contact->type, ['customer', 'both'])) {
            throw new InvalidAccountException('Kontak harus bertipe Customer atau Both.');
        }

        $period = Period::findOrFail($data['period_id']);
        if ($period->is_closed) {
            throw new PeriodClosedException();
        }

        $invoiceDate = Carbon::parse($data['date']);
        if ($invoiceDate->lt($period->start_date) || $invoiceDate->gt($period->end_date)) {
            throw new \Exception(
                "Tanggal invoice ({$data['date']}) berada di luar rentang periode " .
                "{$period->start_date->format('d/m/Y')} s/d {$period->end_date->format('d/m/Y')}."
            );
        }

        (new static())->validatePeriodOpen($invoiceDate);

        $arAccountId = AccountResolver::receivable();

        return DB::transaction(function () use ($data, $items, $taxRules, $contact, $period, $arAccountId) {
            $prefix = Setting::get('invoice_prefix', 'INV');
            $number = \App\Services\DocumentNumberService::generate('invoices', $prefix, $data['date']);

            $invoice = Invoice::create([
                'number' => $number,
                'contact_id' => $data['contact_id'],
                'period_id' => $data['period_id'],
                'date' => $data['date'],
                'due_date' => $data['due_date'] ?? $data['date'],
                'subtotal' => 0,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'tax_amount' => 0,
                'total' => 0,
                'paid_amount' => 0,
                'due_amount' => 0,
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
                'ref_number' => $data['ref_number'] ?? null,
                'created_by' => $data['created_by'] ?? Auth::id(),
                'is_cash_sale' => $data['is_cash_sale'] ?? false,
                'wallet_id' => ($data['is_cash_sale'] ?? false) ? ($data['wallet_id'] ?? null) : null,
            ]);

            $subtotal = 0;

            foreach ($items as $index => $itemData) {
                $product = null;
                if (!empty($itemData['product_id'])) {
                    $product = Product::findOrFail($itemData['product_id']);
                }

                $qty = (float) ($itemData['qty'] ?? 1);
                $unitPrice = (float) ($itemData['unit_price'] ?? 0);
                $discountPercent = (float) ($itemData['discount_percent'] ?? 0);
                $discountAmount = (float) ($itemData['discount_amount'] ?? 0);

                if ($discountPercent > 0) {
                    $discountAmount = ($qty * $unitPrice) * ($discountPercent / 100);
                }

                $itemSubtotal = ($qty * $unitPrice) - $discountAmount;

                $revenueAccountId = $itemData['revenue_account_id']
                    ?? ($product ? $product->revenue_account_id : null)
                    ?? AccountResolver::revenue();

                $cogsAccountId = $itemData['cogs_account_id']
                    ?? ($product ? $product->cogs_account_id : null);

                $costPrice = (float) ($itemData['cost_price']
                    ?? ($product ? $product->purchase_price : 0));

                $description = $itemData['description'] ?? ($product ? $product->name : "Item {$index}");
                $unit = $itemData['unit'] ?? ($product ? $product->unit : null);

                $invoiceItem = InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $product ? $product->id : null,
                    'description' => $description,
                    'qty' => $qty,
                    'unit' => $unit,
                    'unit_price' => $unitPrice,
                    'discount_percent' => $discountPercent,
                    'discount_amount' => $discountAmount,
                    'subtotal' => $itemSubtotal,
                    'revenue_account_id' => $revenueAccountId,
                    'cogs_account_id' => $cogsAccountId,
                    'cost_price' => $costPrice,
                    'sort_order' => $index,
                ]);

                $subtotal += $itemSubtotal;
            }

            $invoiceDiscount = (float) ($data['discount_amount'] ?? 0);
            $netSubtotal = $subtotal - $invoiceDiscount;

            $taxAmount = 0;
            $taxBase = $netSubtotal;

            foreach ($taxRules as $taxData) {
                $taxRule = TaxRule::findOrFail($taxData['tax_rule_id']);

                $method = $taxData['method'] ?? $taxRule->method;
                $rate = (float) ($taxData['rate'] ?? $taxRule->rate);
                $baseAmount = (float) ($taxData['base_amount'] ?? $taxBase);

                $calculatedTax = 0;
                if ($method === 'exclusive') {
                    $calculatedTax = $baseAmount * ($rate / 100);
                } elseif ($method === 'inclusive') {
                    $calculatedTax = $baseAmount - ($baseAmount / (1 + $rate / 100));
                } elseif ($method === 'withholding') {
                    $calculatedTax = $baseAmount * ($rate / 100);
                }

                InvoiceTax::create([
                    'invoice_id' => $invoice->id,
                    'tax_rule_id' => $taxRule->id,
                    'tax_code' => $taxRule->code,
                    'tax_name' => $taxRule->name,
                    'method' => $method,
                    'rate' => $rate,
                    'base_amount' => $baseAmount,
                    'tax_amount' => $calculatedTax,
                    'debit_account_id' => $taxRule->debit_account_id,
                    'credit_account_id' => $taxRule->credit_account_id,
                ]);

                if ($method === 'exclusive') {
                    $taxAmount += $calculatedTax;
                } elseif ($method === 'withholding') {
                    // PPh TIDAK mengurangi total invoice.
                    // PPh adalah potongan saat customer membayar (jika customer adalah PT yang wajib potong PPh).
                    // Contoh: Invoice Rp 1.000.000 + PPN 11% = Rp 1.110.000
                    // Saat bayar, customer potong PPh 23 (2%) = Rp 20.000
                    // Yang dibayar customer = Rp 1.090.000, tapi invoice tetap Rp 1.110.000
                }
            }

            $total = $netSubtotal + $taxAmount;

            $invoice->update([
                'subtotal' => $subtotal,
                'discount_amount' => $invoiceDiscount,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'due_amount' => $total,
            ]);

            return $invoice->load('items', 'taxes');
        });
    }

    public static function postInvoice(Invoice $invoice): Journal
    {
        $service = new static();
        $service->validateNotPosted($invoice);
        $service->validateNotCancelled($invoice);

        if ($invoice->items->isEmpty()) {
            throw new InvalidAccountException('Invoice harus memiliki item.');
        }

        $invoiceDate = Carbon::parse($invoice->date);
        $service->validatePeriodOpen($invoiceDate);

        if ($invoice->is_cash_sale) {
            return self::postCashSaleInvoice($invoice);
        }

        $arAccountId = AccountResolver::receivable();

        foreach ($invoice->items as $item) {
            $product = $item->product;
            if ($product && $product->type === 'goods') {
                $invAccountId = $product->inventory_account_id
                    ?? Setting::get('inventory_account_id')
                    ?? null;
                $cogsAccountId = $item->cogs_account_id
                    ?? $product->cogs_account_id
                    ?? Setting::get('cogs_account_id')
                    ?? null;

                if ($invAccountId && $cogsAccountId) {
                    $currentStock = (float) $product->stock_on_hand;
                    $requiredQty = (float) $item->qty;
                    if ($currentStock < $requiredQty) {
                        throw new \App\Exceptions\InsufficientStockException(
                            "Stok tidak mencukupi untuk produk '{$product->name}'. "
                            . "Stok saat ini: {$currentStock}, diperlukan: {$requiredQty}."
                        );
                    }
                }
            }
        }

        return DB::transaction(function () use ($invoice, $arAccountId) {
            $period = $invoice->period;
            $journalLines = [];
            $inventoryMovements = [];

            $revenueLines = [];
            foreach ($invoice->items as $item) {
                $revenueAccountId = $item->revenue_account_id;
                if (!$revenueAccountId) {
                    $product = $item->product;
                    $revenueAccountId = $product ? $product->revenue_account_id : null;
                    if (!$revenueAccountId) {
                        $revenueAccountId = AccountResolver::revenue();
                    }
                }

                if (!$revenueAccountId) {
                    throw new InvalidAccountException(
                        "Akun pendapatan untuk item '{$item->description}' tidak ditemukan."
                    );
                }

                if (!isset($revenueLines[$revenueAccountId])) {
                    $revenueLines[$revenueAccountId] = 0;
                }
                $revenueLines[$revenueAccountId] += (float) $item->subtotal;

            $product = $item->product;
            if ($product && $product->type === 'goods' && $item->cogs_account_id && $product->inventory_account_id) {
                    $fifoResult = FifoCostService::getFifoCosts($product->id, (float) $item->qty);
                    $cogsValue = $fifoResult['total_cost'];
                    $fifoUnitCost = $fifoResult['unit_cost_avg'];

                    if ($cogsValue > 0) {
                        $journalLines[] = [
                            'account_id' => $item->cogs_account_id,
                            'debit_amount' => $cogsValue,
                            'credit_amount' => 0,
                            'description' => "HPP (FIFO): {$item->description}",
                        ];

                        $journalLines[] = [
                            'account_id' => $product->inventory_account_id,
                            'debit_amount' => 0,
                            'credit_amount' => $cogsValue,
                            'description' => "Pengurangan stok: {$item->description}",
                        ];

                        $inventoryMovement = InventoryMovement::create([
                            'product_id' => $product->id,
                            'date' => $invoice->date,
                            'type' => 'out',
                            'ref_type' => 'invoices',
                            'ref_id' => $invoice->id,
                            'qty' => -(float) $item->qty,
                            'unit_cost' => $fifoUnitCost,
                            'total_cost' => $cogsValue,
                            'description' => "Penjualan: {$invoice->number} - {$item->description}",
                            'journal_id' => null,
                            'created_by' => Auth::id(),
                        ]);
                        $inventoryMovements[] = $inventoryMovement;
                    }
                }
            }

            $totalBaseRevenue = array_sum($revenueLines);
            $invoiceDiscount = (float) $invoice->discount_amount;

            foreach ($revenueLines as $accountId => $amount) {
                $netRevenue = $amount;
                if ($invoiceDiscount > 0 && $totalBaseRevenue > 0) {
                    $netRevenue = $amount - ($invoiceDiscount * ($amount / $totalBaseRevenue));
                }
                if ($netRevenue > 0.0001) {
                    $journalLines[] = [
                        'account_id' => $accountId,
                        'debit_amount' => 0,
                        'credit_amount' => $netRevenue,
                        'description' => 'Pendapatan penjualan',
                    ];
                }
            }

            foreach ($invoice->taxes as $tax) {
                if ((float) $tax->tax_amount == 0) {
                    continue;
                }

                if ($tax->method === 'exclusive') {
                    $journalLines[] = [
                        'account_id' => $tax->credit_account_id,
                        'debit_amount' => 0,
                        'credit_amount' => (float) $tax->tax_amount,
                        'description' => "{$tax->tax_name} ({$tax->tax_code})",
                    ];
                }
            }

            $journalLines[] = [
                'account_id' => $arAccountId,
                'debit_amount' => (float) $invoice->total,
                'credit_amount' => 0,
                'description' => "Piutang: {$invoice->number}",
            ];

            $journalData = [
                'date' => $invoice->date,
                'period_id' => $period->id,
                'description' => "Invoice Penjualan: {$invoice->number}",
                'source' => 'sale',
                'type' => 'normal',
                'ref_type' => 'invoices',
                'ref_id' => $invoice->id,
                'created_by' => Auth::id(),
            ];

            $journal = JournalService::createJournal($journalData, $journalLines);

            foreach ($inventoryMovements as $movement) {
                $movement->update(['journal_id' => $journal->id]);
            }

            $invoice->update([
                'status' => 'posted',
                'journal_id' => $journal->id,
            ]);

            return $journal;
        });
    }

    private static function postCashSaleInvoice(Invoice $invoice): Journal
    {
        $wallet = $invoice->wallet;
        if (!$wallet || !$wallet->account_id) {
            throw new InvalidAccountException(
                'Dompet/Bank untuk penjualan tunai tidak ditemukan atau tidak memiliki akun terkait.'
            );
        }

        $period = $invoice->period;
        $journalLines = [];
        $inventoryMovements = [];

        $revenueLines = [];
        foreach ($invoice->items as $item) {
            $revenueAccountId = $item->revenue_account_id;
            if (!$revenueAccountId) {
                $product = $item->product;
                $revenueAccountId = $product ? $product->revenue_account_id : null;
                if (!$revenueAccountId) {
                    $revenueAccountId = AccountResolver::revenue();
                }
            }

            if (!$revenueAccountId) {
                throw new InvalidAccountException(
                    "Akun pendapatan untuk item '{$item->description}' tidak ditemukan."
                );
            }

            if (!isset($revenueLines[$revenueAccountId])) {
                $revenueLines[$revenueAccountId] = 0;
            }
            $revenueLines[$revenueAccountId] += (float) $item->subtotal;

            $product = $item->product;
                if ($product && $product->type === 'goods' && $item->cogs_account_id && $product->inventory_account_id) {
                    $fifoResult = FifoCostService::getFifoCosts($product->id, (float) $item->qty);
                    $cogsValue = $fifoResult['total_cost'];
                    $fifoUnitCost = $fifoResult['unit_cost_avg'];

                    if ($cogsValue > 0) {
                        $journalLines[] = [
                            'account_id' => $item->cogs_account_id,
                            'debit_amount' => $cogsValue,
                            'credit_amount' => 0,
                            'description' => "HPP (FIFO): {$item->description}",
                        ];

                        $journalLines[] = [
                            'account_id' => $product->inventory_account_id,
                            'debit_amount' => 0,
                            'credit_amount' => $cogsValue,
                            'description' => "Pengurangan stok: {$item->description}",
                        ];

                        $inventoryMovement = InventoryMovement::create([
                            'product_id' => $product->id,
                            'date' => $invoice->date,
                            'type' => 'out',
                            'ref_type' => 'invoices',
                            'ref_id' => $invoice->id,
                            'qty' => -(float) $item->qty,
                            'unit_cost' => $fifoUnitCost,
                            'total_cost' => $cogsValue,
                            'description' => "Penjualan tunai: {$invoice->number} - {$item->description}",
                            'journal_id' => null,
                            'created_by' => Auth::id(),
                        ]);
                        $inventoryMovements[] = $inventoryMovement;
                    }
                }
        }

        $totalBaseRevenue = array_sum($revenueLines);
        $invoiceDiscount = (float) $invoice->discount_amount;

        // Kredit: pendapatan per item
        foreach ($revenueLines as $accountId => $amount) {
            $netRevenue = $amount;
            if ($invoiceDiscount > 0 && $totalBaseRevenue > 0) {
                $netRevenue = $amount - ($invoiceDiscount * ($amount / $totalBaseRevenue));
            }
            if ($netRevenue > 0.0001) {
                $journalLines[] = [
                    'account_id' => $accountId,
                    'debit_amount' => 0,
                    'credit_amount' => $netRevenue,
                    'description' => 'Pendapatan penjualan tunai',
                ];
            }
        }

        // Kredit: PPN Keluaran
        foreach ($invoice->taxes as $tax) {
            if ((float) $tax->tax_amount == 0) continue;
            if ($tax->method === 'exclusive') {
                $journalLines[] = [
                    'account_id' => $tax->credit_account_id,
                    'debit_amount' => 0,
                    'credit_amount' => (float) $tax->tax_amount,
                    'description' => "{$tax->tax_name} ({$tax->tax_code})",
                ];
            }
        }

        // Debit: Kas/Bank (langsung, tanpa piutang)
        $journalLines[] = [
            'account_id' => $wallet->account_id,
            'debit_amount' => (float) $invoice->total,
            'credit_amount' => 0,
            'description' => "Penjualan tunai: {$invoice->number}",
            'wallet_id' => $wallet->id,
        ];

        $journalData = [
            'date' => $invoice->date,
            'period_id' => $period->id,
            'description' => "Penjualan Tunai: {$invoice->number}",
            'source' => 'sale',
            'type' => 'normal',
            'ref_type' => 'invoices',
            'ref_id' => $invoice->id,
            'created_by' => Auth::id(),
        ];

        return DB::transaction(function () use ($invoice, $journalData, $journalLines, $inventoryMovements) {
            $journal = JournalService::createJournal($journalData, $journalLines);

            foreach ($inventoryMovements as $movement) {
                $movement->update(['journal_id' => $journal->id]);
            }

            // Auto-buat payment record
            $payment = \App\Models\Payment::create([
                'number' => \App\Services\DocumentNumberService::generate('payments', 'PAY', $invoice->date),
                'payable_type' => 'invoices',
                'payable_id' => $invoice->id,
                'period_id' => $invoice->period_id,
                'wallet_id' => $invoice->wallet_id,
                'date' => $invoice->date,
                'amount' => (float) $invoice->total,
                'withholding_amount' => 0,
                'method' => 'cash',
                'reference' => 'Penjualan tunai',
                'notes' => 'Pembayaran otomatis untuk penjualan tunai',
                'status' => 'verified',
                'verified_by' => Auth::id(),
                'verified_at' => now(),
                'journal_id' => $journal->id,
                'created_by' => Auth::id(),
            ]);

            $invoice->update([
                'status' => 'paid',
                'paid_amount' => (float) $invoice->total,
                'due_amount' => 0,
                'journal_id' => $journal->id,
            ]);

            return $journal;
        });
    }

    public static function cancelInvoice(Invoice $invoice, ?string $reason = null): void
    {
        $service = new static();
        $service->validateNotCancelled($invoice);

        if (!$invoice->is_cash_sale && $invoice->paid_amount > 0) {
            throw new \App\Exceptions\InvalidStateException('Invoice tidak dapat dibatalkan karena sudah ada pembayaran.');
        }

        $linkedCreditNote = \App\Models\CreditNote::where('invoice_id', $invoice->id)
            ->where('status', '!=', 'cancelled')
            ->first();

        if ($linkedCreditNote) {
            throw new \App\Exceptions\InvalidStateException(
                'Invoice tidak dapat dibatalkan karena masih ada Credit Note terkait yang aktif.'
            );
        }

        DB::transaction(function () use ($invoice, $reason) {
            if ($invoice->journal_id) {
                $journal = Journal::find($invoice->journal_id);
                if ($journal && $journal->type !== 'void') {
                    JournalService::voidJournal($journal, $reason ?? "Pembatalan invoice {$invoice->number}");
                }
            }

            // Cancel auto-generated payment for cash sales
            if ($invoice->is_cash_sale) {
                $payments = \App\Models\Payment::where('payable_type', 'invoices')
                    ->where('payable_id', $invoice->id)
                    ->where('status', '!=', 'cancelled')
                    ->get();
                foreach ($payments as $p) {
                    $p->update(['status' => 'cancelled']);
                }
            }

            $invoice->update([
                'status' => 'cancelled',
                'paid_amount' => 0,
                'due_amount' => 0,
            ]);
        });
    }
}
