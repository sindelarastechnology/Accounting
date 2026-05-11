<?php

namespace App\Services;

use App\Exceptions\InvalidAccountException;
use App\Exceptions\PeriodClosedException;
use App\Helpers\AccountResolver;
use App\Models\Account;
use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\Contact;
use App\Models\InventoryMovement;
use App\Models\Invoice;
use App\Models\Period;
use App\Models\Product;
use App\Models\Setting;
use App\Models\TaxRule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreditNoteService extends BaseTransactionService
{
    public static function createCreditNote(array $data, array $items, array $taxRules = []): CreditNote
    {
        if (empty($items)) {
            throw new InvalidAccountException('Credit note harus memiliki minimal 1 item.');
        }

        $contact = Contact::findOrFail($data['contact_id']);
        if ($contact->type !== 'customer') {
            throw new InvalidAccountException('Kontak harus bertipe Customer.');
        }

        $period = Period::findOrFail($data['period_id']);
        if ($period->is_closed) {
            throw new PeriodClosedException();
        }

        return DB::transaction(function () use ($data, $items, $taxRules) {
            $number = \App\Services\DocumentNumberService::generate('credit_notes', 'CN', $data['date']);

            $creditNote = CreditNote::create([
                'number' => $number,
                'contact_id' => $data['contact_id'],
                'period_id' => $data['period_id'],
                'invoice_id' => $data['invoice_id'] ?? null,
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

                $revenueAccountId = $itemData['revenue_account_id']
                    ?? ($product ? $product->revenue_account_id : null)
                    ?? AccountResolver::revenue();

                $costPrice = (float) ($itemData['cost_price']
                    ?? ($product ? $product->purchase_price : 0));

                $description = $itemData['description'] ?? ($product ? $product->name : "Item {$index}");
                $unit = $itemData['unit'] ?? ($product ? $product->unit : null);

                CreditNoteItem::create([
                    'credit_note_id' => $creditNote->id,
                    'product_id' => $product ? $product->id : null,
                    'invoice_item_id' => $itemData['invoice_item_id'] ?? null,
                    'description' => $description,
                    'qty' => $qty,
                    'unit' => $unit,
                    'unit_price' => $unitPrice,
                    'discount_percent' => $discountPercent,
                    'discount_amount' => $discountAmount,
                    'subtotal' => $itemSubtotal,
                    'revenue_account_id' => $revenueAccountId,
                    'cost_price' => $costPrice,
                    'sort_order' => $index,
                ]);

                $subtotal += $itemSubtotal;
            }

            $cnDiscount = (float) ($data['discount_amount'] ?? 0);
            $netSubtotal = $subtotal - $cnDiscount;

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

                // Simpan tax info (tidak dicatat di journal saat ini, hanya untuk tracking)
                if ($method === 'exclusive') {
                    $taxAmount += $calculatedTax;
                }
            }

            $total = $netSubtotal + $taxAmount;

            $creditNote->update([
                'subtotal' => $subtotal,
                'discount_percent' => (float) ($data['discount_percent'] ?? 0),
                'discount_amount' => $cnDiscount,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'remaining_amount' => $total,
            ]);

            return $creditNote->load('items');
        });
    }

    public static function postCreditNote(CreditNote $creditNote): void
    {
        $service = new static();
        $service->validateNotPosted($creditNote);
        $service->validateNotCancelled($creditNote);

        if ($creditNote->items->isEmpty()) {
            throw new InvalidAccountException('Credit note harus memiliki item.');
        }

        $creditNoteDate = Carbon::parse($creditNote->date);
        $service->validatePeriodOpen($creditNoteDate);

        $arAccountId = AccountResolver::receivable();

        DB::transaction(function () use ($creditNote, $arAccountId) {
            $period = $creditNote->period;
            $journalLines = [];

            // Credit Note: mengurangi piutang dan pendapatan
            // Debit: Pendapatan (mengurangi revenue)
            // Debit: PPN Keluaran (mengurangi PPN output, jika ada)
            // Credit: Piutang Usaha (mengurangi AR)

            $revenueLines = [];
            foreach ($creditNote->items as $item) {
                $revenueAccountId = $item->revenue_account_id;
                if (!$revenueAccountId) {
                    $revenueAccountId = AccountResolver::revenue();
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

                // Jika barang dikembalikan ke stok
                $product = $item->product;
                $returnToStock = $item->product_id && $product && $product->type === 'goods';

                if ($returnToStock && $product->inventory_account_id) {
                    $cogsValue = (float) $item->qty * (float) $item->cost_price;

                    if ($cogsValue > 0) {
                        // Kembalikan stok: Debit Persediaan, Credit HPP
                        $journalLines[] = [
                            'account_id' => $product->inventory_account_id,
                            'debit_amount' => $cogsValue,
                            'credit_amount' => 0,
                            'description' => "Retur stok: {$item->description}",
                        ];

                        $cogsAccountId = $item->invoice_item?->cogs_account_id
                            ?? ($product->cogs_account_id ?? Setting::get('cogs_account_id'));

                        if ($cogsAccountId) {
                            $journalLines[] = [
                                'account_id' => $cogsAccountId,
                                'debit_amount' => 0,
                                'credit_amount' => $cogsValue,
                                'description' => "Pengurangan HPP: {$item->description}",
                            ];
                        }

                        InventoryMovement::create([
                            'product_id' => $product->id,
                            'date' => $creditNote->date,
                            'type' => 'in',
                            'ref_type' => 'credit_notes',
                            'ref_id' => $creditNote->id,
                            'qty' => (float) $item->qty,
                            'unit_cost' => (float) $item->cost_price,
                            'total_cost' => $cogsValue,
                            'description' => "Retur Penjualan: {$creditNote->number} - {$item->description}",
                            'journal_id' => null,
                            'created_by' => Auth::id(),
                        ]);
                    }
                }
            }

            foreach ($revenueLines as $accountId => $amount) {
                if ($amount > 0) {
                    $journalLines[] = [
                        'account_id' => $accountId,
                        'debit_amount' => $amount,
                        'credit_amount' => 0,
                        'description' => 'Retur pendapatan penjualan',
                    ];
                }
            }

            $cnDiscount = (float) $creditNote->discount_amount;
            if ($cnDiscount > 0) {
                $totalRevenue = array_sum($revenueLines);
                foreach ($revenueLines as $accountId => $amount) {
                    $allocatedDiscount = $totalRevenue > 0
                        ? $cnDiscount * ($amount / $totalRevenue)
                        : 0;
                    if ($allocatedDiscount > 0) {
                        $journalLines[] = [
                            'account_id' => $accountId,
                            'credit_amount' => $allocatedDiscount,
                            'debit_amount' => 0,
                            'description' => 'Diskon retur penjualan',
                        ];
                    }
                }
            }

            // Credit PPN Keluaran (mengurangi PPN output)
            if ((float) $creditNote->tax_amount > 0) {
                $ppnOutputId = AccountResolver::taxPayable();

                $journalLines[] = [
                    'account_id' => $ppnOutputId,
                    'debit_amount' => (float) $creditNote->tax_amount,
                    'credit_amount' => 0,
                    'description' => 'Retur PPN Keluaran',
                ];
            }

            // Credit: Piutang Usaha (mengurangi AR)
            $journalLines[] = [
                'account_id' => $arAccountId,
                'debit_amount' => 0,
                'credit_amount' => (float) $creditNote->total,
                'description' => "Credit Note: {$creditNote->number}",
            ];

            $journalData = [
                'date' => $creditNote->date,
                'period_id' => $period->id,
                'description' => "Credit Note (Retur Penjualan): {$creditNote->number}",
                'source' => 'credit_note',
                'type' => 'normal',
                'ref_type' => 'credit_notes',
                'ref_id' => $creditNote->id,
                'created_by' => Auth::id(),
            ];

            $journal = JournalService::createJournal($journalData, $journalLines);

            $creditNote->update([
                'status' => 'posted',
                'journal_id' => $journal->id,
            ]);
        });
    }

    public static function cancelCreditNote(CreditNote $creditNote): void
    {
        if ($creditNote->status === 'cancelled') {
            throw new InvalidAccountException('Credit note sudah dibatalkan.');
        }

        if ($creditNote->applied_amount > 0) {
            throw new InvalidAccountException('Credit note tidak dapat dibatalkan karena sudah diaplikasikan.');
        }

        DB::transaction(function () use ($creditNote) {
            if ($creditNote->journal_id) {
                $journal = \App\Models\Journal::find($creditNote->journal_id);
                if ($journal && $journal->type !== 'void') {
                    JournalService::voidJournal($journal, "Pembatalan credit note {$creditNote->number}");
                }
            }

            $creditNote->update([
                'status' => 'cancelled',
            ]);
        });
    }

    public static function applyCreditNoteToInvoice(CreditNote $creditNote, Invoice $invoice): void
    {
        if ($creditNote->status !== 'posted') {
            throw new InvalidAccountException('Credit note harus dalam status posted.');
        }

        if ($invoice->status === 'cancelled') {
            throw new InvalidAccountException('Invoice sudah dibatalkan.');
        }

        $remaining = (float) $creditNote->remaining_amount;
        $invoiceDue = (float) $invoice->due_amount;

        if ($remaining <= 0) {
            throw new InvalidAccountException('Credit note sudah sepenuhnya diaplikasikan.');
        }

        $applyAmount = min($remaining, $invoiceDue);

        DB::transaction(function () use ($creditNote, $invoice, $applyAmount) {
            $newPaidAmount = (float) $invoice->paid_amount + $applyAmount;
            $newDueAmount = (float) $invoice->total - $newPaidAmount;

            $newStatus = $newDueAmount <= 0.0001 ? 'paid' : 'partially_paid';

            $invoice->update([
                'paid_amount' => $newPaidAmount,
                'due_amount' => max(0, $newDueAmount),
                'status' => $newStatus,
            ]);

            $creditNote->update([
                'applied_amount' => (float) $creditNote->applied_amount + $applyAmount,
                'remaining_amount' => max(0, (float) $creditNote->remaining_amount - $applyAmount),
                'status' => ((float) $creditNote->remaining_amount - $applyAmount) <= 0.0001
                    ? 'applied'
                    : 'posted',
            ]);
        });
    }
}
