<?php

namespace App\Services;

use App\Exceptions\InvalidAccountException;
use App\Exceptions\PeriodClosedException;
use App\Helpers\AccountResolver;
use App\Models\Account;
use App\Models\DebitNote;
use App\Models\DebitNoteItem;
use App\Models\Contact;
use App\Models\InventoryMovement;
use App\Models\Period;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Setting;
use App\Models\TaxRule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DebitNoteService extends BaseTransactionService
{
    public static function createDebitNote(array $data, array $items, array $taxRules = []): DebitNote
    {
        if (empty($items)) {
            throw new InvalidAccountException('Debit note harus memiliki minimal 1 item.');
        }

        $contact = Contact::findOrFail($data['contact_id']);
        if ($contact->type !== 'supplier') {
            throw new InvalidAccountException('Kontak harus bertipe Supplier.');
        }

        $period = Period::findOrFail($data['period_id']);
        if ($period->is_closed) {
            throw new PeriodClosedException();
        }

        return DB::transaction(function () use ($data, $items, $taxRules) {
            $number = \App\Services\DocumentNumberService::generate('debit_notes', 'DN', $data['date']);

            $debitNote = DebitNote::create([
                'number' => $number,
                'contact_id' => $data['contact_id'],
                'period_id' => $data['period_id'],
                'purchase_id' => $data['purchase_id'] ?? null,
                'date' => $data['date'],
                'reason' => $data['reason'] ?? null,
                'subtotal' => 0,
                'discount_percent' => 0,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'total' => 0,
                'applied_amount' => 0,
                'remaining_amount' => 0,
                'status' => 'draft',
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
                    ?? ($product ? $product->purchase_account_id : null)
                    ?? ($product && $product->type === 'goods'
                        ? AccountResolver::inventory()
                        : AccountResolver::expense());

                if (!$accountId) {
                    $accountId = AccountResolver::expense();
                }

                $description = $itemData['description'] ?? ($product ? $product->name : "Item {$index}");
                $unit = $itemData['unit'] ?? ($product ? $product->unit : null);

                $costPrice = (float) ($itemData['cost_price']
                    ?? ($product ? $product->purchase_price : 0));

                DebitNoteItem::create([
                    'debit_note_id' => $debitNote->id,
                    'product_id' => $product ? $product->id : null,
                    'purchase_item_id' => $itemData['purchase_item_id'] ?? null,
                    'description' => $description,
                    'qty' => $qty,
                    'unit' => $unit,
                    'unit_price' => $unitPrice,
                    'discount_percent' => $discountPercent,
                    'discount_amount' => $discountAmount,
                    'subtotal' => $itemSubtotal,
                    'account_id' => $accountId,
                    'cost_price' => $costPrice,
                    'sort_order' => $index,
                ]);

                $subtotal += $itemSubtotal;
            }

            $dnDiscount = (float) ($data['discount_amount'] ?? 0);
            $netSubtotal = $subtotal - $dnDiscount;

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
                }

                if ($method === 'exclusive') {
                    $taxAmount += $calculatedTax;
                }
            }

            $total = $netSubtotal + $taxAmount;

            $debitNote->update([
                'subtotal' => $subtotal,
                'discount_percent' => (float) ($data['discount_percent'] ?? 0),
                'discount_amount' => $dnDiscount,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'remaining_amount' => $total,
            ]);

            return $debitNote->load('items');
        });
    }

    public static function postDebitNote(DebitNote $debitNote): void
    {
        $service = new static();
        $service->validateNotPosted($debitNote);
        $service->validateNotCancelled($debitNote);

        if ($debitNote->items->isEmpty()) {
            throw new InvalidAccountException('Debit note harus memiliki item.');
        }

        $debitNoteDate = Carbon::parse($debitNote->date);
        $service->validatePeriodOpen($debitNoteDate);

        $apAccountId = AccountResolver::payable();

        DB::transaction(function () use ($debitNote, $apAccountId) {
            $period = $debitNote->period;
            $journalLines = [];

            // Debit Note: mengurangi hutang dan expense/persediaan
            // Debit: Hutang Usaha (mengurangi AP)
            // Credit: Expense/Persediaan (mengurangi beban)
            // Credit: PPN Masukan (mengurangi PPN input, jika ada)

            $expenseLines = [];
            foreach ($debitNote->items as $item) {
                $accountId = $item->account_id;
                if (!$accountId) {
                    $accountId = AccountResolver::inventory();
                }

                if (!$accountId) {
                    throw new InvalidAccountException(
                        "Akun untuk item '{$item->description}' tidak ditemukan."
                    );
                }

                if (!isset($expenseLines[$accountId])) {
                    $expenseLines[$accountId] = 0;
                }
                $expenseLines[$accountId] += (float) $item->subtotal;

                // Jika barang dikembalikan ke supplier (kurangi stok)
                $product = $item->product;
                $returnToSupplier = $item->product_id && $product && $product->type === 'goods';

                if ($returnToSupplier && $product->inventory_account_id) {
                    $cogsValue = (float) $item->qty * (float) $item->cost_price;

                    if ($cogsValue > 0) {
                        InventoryMovement::create([
                            'product_id' => $product->id,
                            'date' => $debitNote->date,
                            'type' => 'out',
                            'ref_type' => 'debit_notes',
                            'ref_id' => $debitNote->id,
                            'qty' => -(float) $item->qty,
                            'unit_cost' => (float) $item->cost_price,
                            'total_cost' => $cogsValue,
                            'description' => "Retur Pembelian: {$debitNote->number} - {$item->description}",
                            'journal_id' => null,
                            'created_by' => Auth::id(),
                        ]);
                    }
                }
            }

            foreach ($expenseLines as $accountId => $amount) {
                if ($amount > 0) {
                    $journalLines[] = [
                        'account_id' => $accountId,
                        'debit_amount' => 0,
                        'credit_amount' => $amount,
                        'description' => 'Retur pembelian/pengurangan beban',
                    ];
                }
            }

            $dnDiscount = (float) $debitNote->discount_amount;
            if ($dnDiscount > 0) {
                $totalCost = array_sum($expenseLines);
                foreach ($expenseLines as $accountId => $amount) {
                    $allocatedDiscount = $totalCost > 0
                        ? $dnDiscount * ($amount / $totalCost)
                        : 0;
                    if ($allocatedDiscount > 0) {
                        $journalLines[] = [
                            'account_id' => $accountId,
                            'debit_amount' => $allocatedDiscount,
                            'credit_amount' => 0,
                            'description' => 'Diskon retur pembelian',
                        ];
                    }
                }
            }

            // Credit PPN Masukan (mengurangi PPN input)
            if ((float) $debitNote->tax_amount > 0) {
                $ppnInputId = AccountResolver::ppnInput();

                $journalLines[] = [
                    'account_id' => $ppnInputId,
                    'debit_amount' => 0,
                    'credit_amount' => (float) $debitNote->tax_amount,
                    'description' => 'Retur PPN Masukan',
                ];
            }

            // Debit: Hutang Usaha (mengurangi AP)
            $journalLines[] = [
                'account_id' => $apAccountId,
                'debit_amount' => (float) $debitNote->total,
                'credit_amount' => 0,
                'description' => "Debit Note: {$debitNote->number}",
            ];

            $journalData = [
                'date' => $debitNote->date,
                'period_id' => $period->id,
                'description' => "Debit Note (Retur Pembelian): {$debitNote->number}",
                'source' => 'debit_note',
                'type' => 'normal',
                'ref_type' => 'debit_notes',
                'ref_id' => $debitNote->id,
                'created_by' => Auth::id(),
            ];

            $journal = JournalService::createJournal($journalData, $journalLines);

            $debitNote->update([
                'status' => 'posted',
                'journal_id' => $journal->id,
            ]);
        });
    }

    public static function cancelDebitNote(DebitNote $debitNote): void
    {
        if ($debitNote->status === 'cancelled') {
            throw new InvalidAccountException('Debit note sudah dibatalkan.');
        }

        if ($debitNote->applied_amount > 0) {
            throw new InvalidAccountException('Debit note tidak dapat dibatalkan karena sudah diaplikasikan.');
        }

        DB::transaction(function () use ($debitNote) {
            if ($debitNote->journal_id) {
                $journal = \App\Models\Journal::find($debitNote->journal_id);
                if ($journal && $journal->type !== 'void') {
                    JournalService::voidJournal($journal, "Pembatalan debit note {$debitNote->number}");
                }
            }

            $debitNote->update([
                'status' => 'cancelled',
            ]);
        });
    }

    public static function applyDebitNoteToPurchase(DebitNote $debitNote, Purchase $purchase): void
    {
        if ($debitNote->status !== 'posted') {
            throw new InvalidAccountException('Debit note harus dalam status posted.');
        }

        if ($purchase->status === 'cancelled') {
            throw new InvalidAccountException('Pembelian sudah dibatalkan.');
        }

        $remaining = (float) $debitNote->remaining_amount;
        $purchaseDue = (float) $purchase->due_amount;

        if ($remaining <= 0) {
            throw new InvalidAccountException('Debit note sudah sepenuhnya diaplikasikan.');
        }

        $applyAmount = min($remaining, $purchaseDue);

        DB::transaction(function () use ($debitNote, $purchase, $applyAmount) {
            $newPaidAmount = (float) $purchase->paid_amount + $applyAmount;
            $newDueAmount = (float) $purchase->total - $newPaidAmount;

            $newStatus = $newDueAmount <= 0.0001 ? 'paid' : 'partially_paid';

            $purchase->update([
                'paid_amount' => $newPaidAmount,
                'due_amount' => max(0, $newDueAmount),
                'status' => $newStatus,
            ]);

            $debitNote->update([
                'applied_amount' => (float) $debitNote->applied_amount + $applyAmount,
                'remaining_amount' => max(0, (float) $debitNote->remaining_amount - $applyAmount),
                'status' => ((float) $debitNote->remaining_amount - $applyAmount) <= 0.0001
                    ? 'applied'
                    : 'posted',
            ]);
        });
    }
}
