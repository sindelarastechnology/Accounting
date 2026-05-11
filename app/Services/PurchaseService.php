<?php

namespace App\Services;

use App\Exceptions\InvalidAccountException;
use App\Exceptions\InvalidStateException;
use App\Exceptions\PeriodClosedException;
use App\Helpers\AccountResolver;
use App\Models\Account;
use App\Models\Contact;
use App\Models\InventoryMovement;
use App\Models\Journal;
use App\Models\Period;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseTax;
use App\Models\Setting;
use App\Models\TaxRule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseService extends BaseTransactionService
{
    public static function createPurchase(array $data, array $items, array $taxRules = []): Purchase
    {
        if (empty($items)) {
            throw new InvalidAccountException('Pembelian harus memiliki minimal 1 item.');
        }

        $contact = Contact::findOrFail($data['contact_id']);
        if (!in_array($contact->type, ['supplier', 'both'])) {
            throw new InvalidAccountException('Kontak harus bertipe Supplier atau Both.');
        }

        $period = Period::findOrFail($data['period_id']);
        if ($period->is_closed) {
            throw new PeriodClosedException();
        }

        $purchaseDate = Carbon::parse($data['date']);
        if ($purchaseDate->lt($period->start_date) || $purchaseDate->gt($period->end_date)) {
            throw new \Exception(
                "Tanggal pembelian berada di luar rentang periode aktif."
            );
        }

        return DB::transaction(function () use ($data, $items, $taxRules) {
            $prefix = Setting::get('purchase_prefix', 'PO');
            $number = \App\Services\DocumentNumberService::generate('purchases', $prefix, $data['date']);

            $purchase = Purchase::create([
                'number' => $number,
                'contact_id' => $data['contact_id'],
                'period_id' => $data['period_id'],
                'date' => $data['date'],
                'due_date' => $data['due_date'] ?? $data['date'],
                'supplier_invoice_number' => $data['supplier_invoice_number'] ?? null,
                'subtotal' => 0,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'tax_amount' => 0,
                'total' => 0,
                'paid_amount' => 0,
                'due_amount' => 0,
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'] ?? Auth::id(),
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

                $accountId = $itemData['account_id']
                    ?? ($product ? $product->purchase_account_id : null);

                if ($product && $product->type === 'goods') {
                    // Untuk barang, gunakan akun inventori
                    if (!$accountId) {
                        $accountId = $product->inventory_account_id
                            ?? AccountResolver::inventory();
                    }
                } else {
                    // Untuk non-barang (jasa/beban), gunakan akun beban
                    if (!$accountId) {
                        $accountId = AccountResolver::expense();
                    }
                }

                if (!$accountId) {
                    throw new InvalidAccountException(
                        "Akun untuk item '{$itemData['description']}' tidak ditemukan."
                    );
                }

                $description = $itemData['description'] ?? ($product ? $product->name : "Item {$index}");
                $unit = $itemData['unit'] ?? ($product ? $product->unit : null);

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $product ? $product->id : null,
                    'description' => $description,
                    'qty' => $qty,
                    'unit' => $unit,
                    'unit_price' => $unitPrice,
                    'discount_percent' => $discountPercent,
                    'discount_amount' => $discountAmount,
                    'subtotal' => $itemSubtotal,
                    'account_id' => $accountId,
                    'sort_order' => $index,
                ]);

                $subtotal += $itemSubtotal;
            }

            $purchaseDiscount = (float) ($data['discount_amount'] ?? 0);
            $netSubtotal = $subtotal - $purchaseDiscount;

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

                PurchaseTax::create([
                    'purchase_id' => $purchase->id,
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
                    // PPh tidak masuk ke total purchase.
                    // PPh adalah potongan saat pembayaran ke supplier.
                    // Contoh: Purchase Rp 1.000.000 + PPN 11% = Rp 1.110.000
                    // PPh 23 (2%) = Rp 20.000 → dipotong saat bayar
                    // Yang dibayar ke supplier = Rp 1.110.000 - Rp 20.000 = Rp 1.090.000
                }
            }

            $total = $netSubtotal + $taxAmount;

            $purchase->update([
                'subtotal' => $subtotal,
                'discount_amount' => $purchaseDiscount,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'due_amount' => $total,
            ]);

            return $purchase->load('items', 'taxes');
        });
    }

    public static function postPurchase(Purchase $purchase): Journal
    {
        $service = new static();
        $service->validateNotPosted($purchase);
        $service->validateNotCancelled($purchase);

        if ($purchase->items->isEmpty()) {
            throw new InvalidAccountException('Pembelian harus memiliki item.');
        }

        $purchaseDate = Carbon::parse($purchase->date);
        $service->validatePeriodOpen($purchaseDate);

        $apAccountId = AccountResolver::payable();

        return DB::transaction(function () use ($purchase, $apAccountId) {
            $period = $purchase->period;
            $journalLines = [];
            $inventoryMovements = [];

            $inventoryLines = [];
            $expenseLines = [];
            
            foreach ($purchase->items as $item) {
                $product = $item->product;
                
                if ($product && $product->type === 'goods') {
                    $accountId = $product->inventory_account_id
                        ?? AccountResolver::inventory();
                    
                    if (!$accountId) {
                        throw new InvalidAccountException(
                            "Akun inventori untuk produk '{$product->name}' tidak ditemukan."
                        );
                    }
                    
                    if (!isset($inventoryLines[$accountId])) {
                        $inventoryLines[$accountId] = 0;
                    }
                    $inventoryLines[$accountId] += (float) $item->subtotal;

                    $costValue = (float) $item->qty * (float) $item->unit_price;
                    if ($costValue > 0) {
                        $movement = InventoryMovement::create([
                            'product_id' => $product->id,
                            'date' => $purchase->date,
                            'type' => 'in',
                            'ref_type' => 'purchases',
                            'ref_id' => $purchase->id,
                            'qty' => (float) $item->qty,
                            'unit_cost' => (float) $item->unit_price,
                            'total_cost' => $costValue,
                            'description' => "Pembelian: {$purchase->number} - {$item->description}",
                            'journal_id' => null,
                            'created_by' => Auth::id(),
                        ]);
                        $inventoryMovements[] = $movement;
                    }
                } else {
                    $accountId = $item->account_id
                        ?? ($product ? $product->purchase_account_id : null)
                        ?? AccountResolver::expense();

                    if (!$accountId) {
                        throw new InvalidAccountException(
                            "Akun untuk item '{$item->description}' tidak ditemukan."
                        );
                    }

                    if (!isset($expenseLines[$accountId])) {
                        $expenseLines[$accountId] = 0;
                    }
                    $expenseLines[$accountId] += (float) $item->subtotal;
                }
            }

            // Tambah baris inventori ke journal
            foreach ($inventoryLines as $accountId => $amount) {
                if ($amount > 0) {
                    $journalLines[] = [
                        'account_id' => $accountId,
                        'debit_amount' => $amount,
                        'credit_amount' => 0,
                        'description' => 'Pembelian barang (inventori)',
                    ];
                }
            }

            // Tambah baris beban ke journal
            foreach ($expenseLines as $accountId => $amount) {
                if ($amount > 0) {
                    $journalLines[] = [
                        'account_id' => $accountId,
                        'debit_amount' => $amount,
                        'credit_amount' => 0,
                        'description' => 'Pembelian beban/jasa',
                    ];
                }
            }

            $purchaseDiscount = (float) $purchase->discount_amount;
            if ($purchaseDiscount > 0) {
                $allLines = array_merge($inventoryLines, $expenseLines);
                $totalCost = array_sum($allLines);
                foreach ($allLines as $accountId => $amount) {
                    $allocatedDiscount = $totalCost > 0
                        ? $purchaseDiscount * ($amount / $totalCost)
                        : 0;
                    if ($allocatedDiscount > 0) {
                        $journalLines[] = [
                            'account_id' => $accountId,
                            'debit_amount' => 0,
                            'credit_amount' => $allocatedDiscount,
                            'description' => 'Diskon pembelian (pengurang biaya)',
                        ];
                    }
                }
            }

            foreach ($purchase->taxes as $tax) {
                if ((float) $tax->tax_amount == 0) {
                    continue;
                }

                // Hanya PPN (exclusive) yang dicatat saat posting purchase.
                // PPh (withholding) TIDAK dicatat di sini — ditangani saat pembayaran.
                // Alasan: PPh adalah potongan dari pembayaran ke supplier, bukan bagian dari nilai purchase.
                if ($tax->method === 'exclusive') {
                    $journalLines[] = [
                        'account_id' => $tax->debit_account_id,
                        'debit_amount' => (float) $tax->tax_amount,
                        'credit_amount' => 0,
                        'description' => "{$tax->tax_name} ({$tax->tax_code})",
                    ];
                }
            }

            $journalLines[] = [
                'account_id' => $apAccountId,
                'debit_amount' => 0,
                'credit_amount' => (float) $purchase->total,
                'description' => "Hutang: {$purchase->number}",
            ];

            $journalData = [
                'date' => $purchase->date,
                'period_id' => $period->id,
                'description' => "Pembelian: {$purchase->number}",
                'source' => 'purchase',
                'type' => 'normal',
                'ref_type' => 'purchases',
                'ref_id' => $purchase->id,
                'created_by' => Auth::id(),
            ];

            $journal = JournalService::createJournal($journalData, $journalLines);

            // Update journal_id in inventory movements within the same transaction
            foreach ($inventoryMovements as $movement) {
                $movement->update(['journal_id' => $journal->id]);
            }

            $purchase->update([
                'status' => 'posted',
                'journal_id' => $journal->id,
            ]);

            return $journal;
        });
    }

    public static function cancelPurchase(Purchase $purchase): void
    {
        if ($purchase->status === 'cancelled') {
            throw new InvalidStateException('Pembelian sudah dibatalkan.');
        }

        if ($purchase->paid_amount > 0) {
            throw new InvalidStateException('Pembelian tidak dapat dibatalkan karena sudah ada pembayaran.');
        }

        DB::transaction(function () use ($purchase) {
            if ($purchase->journal_id) {
                $journal = \App\Models\Journal::find($purchase->journal_id);
                if ($journal && $journal->type !== 'void') {
                    JournalService::voidJournal($journal, "Pembatalan pembelian {$purchase->number}");
                }
            }

            $purchase->update([
                'status' => 'cancelled',
            ]);
        });
    }
}
