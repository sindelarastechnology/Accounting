<?php

namespace App\Services;

use App\Helpers\AccountResolver;
use App\Models\Account;
use App\Models\Contact;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\Invoice;
use App\Models\InventoryMovement;
use App\Models\Journal;
use App\Models\JournalLine;
use App\Models\Product;
use App\Models\Purchase;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LedgerService
{
    public static function sourceColor(string $source): string
    {
        return match ($source) {
            'manual'        => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
            'sale'          => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400',
            'purchase'      => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400',
            'payment'       => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-400',
            'expense'       => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400',
            'opening'       => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-400',
            'closing'       => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-400',
            'fixed_asset'   => 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-400',
            'depreciation'  => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-400',
            'credit_note'   => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400',
            'debit_note'    => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400',
            'transfer'      => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-400',
            'other_receipt' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-400',
            'stock_opname'  => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
            'system'        => 'bg-gray-300 text-gray-800 dark:bg-gray-600 dark:text-gray-200',
            default         => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        };
    }

    public static function sourceLabel(string $source): string
    {
        return match ($source) {
            'manual'        => 'Manual',
            'sale'          => 'Penjualan',
            'purchase'      => 'Pembelian',
            'payment'       => 'Pembayaran',
            'expense'       => 'Beban',
            'opening'       => 'Saldo Awal',
            'closing'       => 'Penutup',
            'fixed_asset'   => 'Aset Tetap',
            'depreciation'  => 'Penyusutan',
            'credit_note'   => 'Retur Penjualan',
            'debit_note'    => 'Retur Pembelian',
            'transfer'      => 'Transfer Kas',
            'other_receipt' => 'Kas Masuk',
            'stock_opname'  => 'Stock Opname',
            'system'        => 'Sistem',
            default         => $source,
        };
    }

    public static function categoryColor(string $category): string
    {
        return match ($category) {
            'asset'     => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400',
            'liability' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400',
            'equity'    => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-400',
            'revenue'   => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400',
            'expense'   => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-400',
            'cogs'      => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-400',
            default     => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        };
    }

    public static function categoryLabel(string $category): string
    {
        return match ($category) {
            'asset'     => 'Aset',
            'liability' => 'Kewajiban',
            'equity'    => 'Modal',
            'revenue'   => 'Pendapatan',
            'expense'   => 'Biaya',
            'cogs'      => 'HPP',
            default     => $category,
        };
    }

    public static function getGeneralLedger(
        ?int $accountId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?string $categoryType = null,
        bool $showEmpty = false,
    ): array {
        if (!$dateFrom) $dateFrom = now()->startOfMonth()->format('Y-m-d');
        if (!$dateTo) $dateTo = now()->format('Y-m-d');

        $accounts = Account::where('is_header', false)->where('is_active', true);
        if ($accountId) $accounts->where('id', $accountId);
        if ($categoryType && $categoryType !== 'all') {
            $accounts->where('category', $categoryType);
        }
        $accounts = $accounts->orderBy('code')->get();

        $openingData = DB::table('journal_lines')
            ->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
            ->where('journals.type', '!=', 'void')
            ->where('journals.date', '<', $dateFrom)
            ->selectRaw('
                journal_lines.account_id,
                COALESCE(SUM(journal_lines.debit_amount), 0) - COALESCE(SUM(journal_lines.credit_amount), 0) as balance
            ')
            ->groupBy('journal_lines.account_id')
            ->get()
            ->keyBy('account_id');

        $periodQuery = DB::table('journal_lines')
            ->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
            ->where('journals.type', '!=', 'void')
            ->where('journals.date', '>=', $dateFrom)
            ->where('journals.date', '<=', $dateTo);

        if ($accountId) {
            $periodQuery->where('journal_lines.account_id', $accountId);
        }

        $periodLines = $periodQuery
            ->orderBy('journals.date')
            ->orderBy('journals.id')
            ->orderBy('journal_lines.id')
            ->select(
                'journal_lines.*',
                'journals.id as journal_id',
                'journals.number as journal_number',
                'journals.date as journal_date',
                'journals.description as journal_description',
                'journals.source as journal_source',
            )
            ->get()
            ->groupBy('account_id');

        $result = [];
        foreach ($accounts as $account) {
            $opening = $openingData->get($account->id);
            $rawBalance = (float) ($opening->balance ?? 0);

            if ($account->normal_balance === 'credit') {
                $openingBalance = -$rawBalance;
            } else {
                $openingBalance = $rawBalance;
            }

            $lines = $periodLines->get($account->id, collect());

            $transactions = [];
            $runningBalance = $openingBalance;

            foreach ($lines as $line) {
                $debit = (float) $line->debit_amount;
                $credit = (float) $line->credit_amount;

                if ($account->normal_balance === 'debit') {
                    $runningBalance += $debit - $credit;
                } else {
                    $runningBalance += $credit - $debit;
                }

                $desc = $line->journal_description;
                if ($line->description) {
                    $desc .= ' - ' . $line->description;
                }

                $transactions[] = [
                    'date' => $line->journal_date,
                    'journal_id' => $line->journal_id,
                    'journal_number' => $line->journal_number,
                    'description' => $desc,
                    'source' => $line->journal_source ?? 'manual',
                    'debit' => $debit,
                    'credit' => $credit,
                    'balance' => $runningBalance,
                ];
            }

            $hasActivity = $openingBalance != 0 || $transactions;

            if (!$showEmpty && !$hasActivity) {
                continue;
            }

            $totalDebit = collect($transactions)->sum('debit');
            $totalCredit = collect($transactions)->sum('credit');

            if ($account->normal_balance === 'debit') {
                $closingBalance = $openingBalance + $totalDebit - $totalCredit;
            } else {
                $closingBalance = $openingBalance + $totalCredit - $totalDebit;
            }

            $result[] = [
                'account' => $account,
                'category_color' => self::categoryColor($account->category),
                'category_label' => self::categoryLabel($account->category),
                'opening_balance' => $openingBalance,
                'closing_balance' => $closingBalance,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'transactions' => $transactions,
            ];
        }

        return [
            'accounts' => $result,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }

    public static function getArLedger(?int $contactId = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        if (!$dateFrom) $dateFrom = now()->startOfMonth()->format('Y-m-d');
        if (!$dateTo) $dateTo = now()->format('Y-m-d');

        $contacts = Contact::whereIn('type', ['customer', 'both'])->orderBy('name');
        if ($contactId) $contacts->where('id', $contactId);
        $contacts = $contacts->get();

        $arAccountId = AccountResolver::receivable();
        $dayBeforeStart = Carbon::parse($dateFrom)->subDay()->format('Y-m-d');

        $result = [];
        foreach ($contacts as $contact) {
            $openingBalance = self::getArBalanceUpTo($contact->id, $arAccountId, $dayBeforeStart);

            $transactions = [];

            $invoices = Invoice::where('contact_id', $contact->id)
                ->whereIn('status', ['posted', 'partially_paid', 'paid'])
                ->where('date', '>=', $dateFrom)
                ->where('date', '<=', $dateTo)
                ->orderBy('date')->orderBy('id')
                ->get();

            foreach ($invoices as $inv) {
                $transactions[] = [
                    'date' => $inv->date,
                    'document' => $inv->number,
                    'description' => 'Invoice',
                    'debit' => (float) $inv->total,
                    'credit' => 0,
                    'source' => 'sale',
                    'ref_type' => 'invoice',
                    'ref_id' => $inv->id,
                    'created_at' => (string) $inv->created_at,
                ];
            }

            $payments = DB::table('payments')
                ->where('payable_type', 'invoices')
                ->whereIn('payable_id', Invoice::where('contact_id', $contact->id)->pluck('id'))
                ->where('status', 'verified')
                ->where('date', '>=', $dateFrom)
                ->where('date', '<=', $dateTo)
                ->orderBy('date')->orderBy('id')
                ->get();

            foreach ($payments as $pay) {
                $transactions[] = [
                    'date' => $pay->date,
                    'document' => $pay->number,
                    'description' => 'Pembayaran' . ($pay->withholding_amount > 0 ? ' (PPh dipotong)' : ''),
                    'debit' => 0,
                    'credit' => (float) $pay->amount,
                    'source' => 'payment',
                    'ref_type' => 'payment',
                    'ref_id' => $pay->id,
                    'created_at' => (string) $pay->created_at,
                ];
            }

            $creditNotes = CreditNote::where('contact_id', $contact->id)
                ->whereIn('status', ['posted', 'applied'])
                ->where('date', '>=', $dateFrom)
                ->where('date', '<=', $dateTo)
                ->orderBy('date')->orderBy('id')
                ->get();

            foreach ($creditNotes as $cn) {
                $transactions[] = [
                    'date' => $cn->date,
                    'document' => $cn->number,
                    'description' => 'Credit Note (Retur)',
                    'debit' => 0,
                    'credit' => (float) $cn->total,
                    'source' => 'credit_note',
                    'ref_type' => 'credit_note',
                    'ref_id' => $cn->id,
                    'created_at' => (string) $cn->created_at,
                ];
            }

            usort($transactions, fn ($a, $b) => [$a['date'], $a['created_at']] <=> [$b['date'], $b['created_at']]);

            $runningBalance = $openingBalance;
            foreach ($transactions as &$tx) {
                $runningBalance += $tx['debit'] - $tx['credit'];
                $tx['balance'] = $runningBalance;
            }

            $closingBalance = $openingBalance + collect($transactions)->sum('debit') - collect($transactions)->sum('credit');

            $result[] = [
                'contact' => $contact,
                'opening_balance' => $openingBalance,
                'closing_balance' => $closingBalance,
                'transactions' => $transactions,
            ];
        }

        return [
            'contacts' => $result,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'type' => 'receivable',
        ];
    }

    public static function getApLedger(?int $contactId = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        if (!$dateFrom) $dateFrom = now()->startOfMonth()->format('Y-m-d');
        if (!$dateTo) $dateTo = now()->format('Y-m-d');

        $contacts = Contact::whereIn('type', ['supplier', 'both'])->orderBy('name');
        if ($contactId) $contacts->where('id', $contactId);
        $contacts = $contacts->get();

        $apAccountId = AccountResolver::payable();
        $dayBeforeStart = Carbon::parse($dateFrom)->subDay()->format('Y-m-d');

        $result = [];
        foreach ($contacts as $contact) {
            $openingBalance = self::getApBalanceUpTo($contact->id, $apAccountId, $dayBeforeStart);

            $transactions = [];

            $purchases = Purchase::where('contact_id', $contact->id)
                ->whereIn('status', ['posted', 'partially_paid', 'paid'])
                ->where('date', '>=', $dateFrom)
                ->where('date', '<=', $dateTo)
                ->orderBy('date')->orderBy('id')
                ->get();

            foreach ($purchases as $pur) {
                $transactions[] = [
                    'date' => $pur->date,
                    'document' => $pur->number,
                    'description' => 'Pembelian',
                    'debit' => 0,
                    'credit' => (float) $pur->total,
                    'source' => 'purchase',
                    'ref_type' => 'purchase',
                    'ref_id' => $pur->id,
                    'created_at' => (string) $pur->created_at,
                ];
            }

            $payments = DB::table('payments')
                ->where('payable_type', 'purchases')
                ->whereIn('payable_id', Purchase::where('contact_id', $contact->id)->pluck('id'))
                ->where('status', 'verified')
                ->where('date', '>=', $dateFrom)
                ->where('date', '<=', $dateTo)
                ->orderBy('date')->orderBy('id')
                ->get();

            foreach ($payments as $pay) {
                $transactions[] = [
                    'date' => $pay->date,
                    'document' => $pay->number,
                    'description' => 'Pembayaran' . ($pay->withholding_amount > 0 ? ' (PPh dipotong)' : ''),
                    'debit' => (float) $pay->amount,
                    'credit' => 0,
                    'source' => 'payment',
                    'ref_type' => 'payment',
                    'ref_id' => $pay->id,
                    'created_at' => (string) $pay->created_at,
                ];
            }

            $debitNotes = DebitNote::where('contact_id', $contact->id)
                ->whereIn('status', ['posted', 'applied'])
                ->where('date', '>=', $dateFrom)
                ->where('date', '<=', $dateTo)
                ->orderBy('date')->orderBy('id')
                ->get();

            foreach ($debitNotes as $dn) {
                $transactions[] = [
                    'date' => $dn->date,
                    'document' => $dn->number,
                    'description' => 'Debit Note (Retur)',
                    'debit' => (float) $dn->total,
                    'credit' => 0,
                    'source' => 'debit_note',
                    'ref_type' => 'debit_note',
                    'ref_id' => $dn->id,
                    'created_at' => (string) $dn->created_at,
                ];
            }

            usort($transactions, fn ($a, $b) => [$a['date'], $a['created_at']] <=> [$b['date'], $b['created_at']]);

            $runningBalance = $openingBalance;
            foreach ($transactions as &$tx) {
                $runningBalance += $tx['credit'] - $tx['debit'];
                $tx['balance'] = $runningBalance;
            }

            $closingBalance = $openingBalance + collect($transactions)->sum('credit') - collect($transactions)->sum('debit');

            $result[] = [
                'contact' => $contact,
                'opening_balance' => $openingBalance,
                'closing_balance' => $closingBalance,
                'transactions' => $transactions,
            ];
        }

        return [
            'contacts' => $result,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'type' => 'payable',
        ];
    }

    public static function getInventoryLedger(?int $productId = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        if (!$dateFrom) $dateFrom = now()->startOfMonth()->format('Y-m-d');
        if (!$dateTo) $dateTo = now()->format('Y-m-d');

        $products = Product::where('type', 'goods')->orderBy('name');
        if ($productId) $products->where('id', $productId);
        $products = $products->get();

        $dayBeforeStart = Carbon::parse($dateFrom)->subDay()->format('Y-m-d');

        $result = [];
        foreach ($products as $product) {
            $beforeMovements = InventoryMovement::where('product_id', $product->id)
                ->where('date', '<=', $dayBeforeStart)
                ->orderBy('date')->orderBy('id')
                ->get();

            $runningQty = 0;
            $runningValue = 0;

            foreach ($beforeMovements as $mov) {
                $qty = (float) $mov->qty;
                if ($mov->type === 'in') {
                    $runningQty += $qty;
                    $runningValue += abs((float) $mov->total_cost);
                } else {
                    $avgCost = $runningQty > 0 ? $runningValue / $runningQty : 0;
                    $runningQty -= abs($qty);
                    $runningValue -= abs($qty) * $avgCost;
                    $runningValue = max(0, $runningValue);
                }
            }

            $openingStock = $runningQty;
            $openingValue = $runningValue;

            $movements = InventoryMovement::where('product_id', $product->id)
                ->where('date', '>=', $dateFrom)
                ->where('date', '<=', $dateTo)
                ->orderBy('date')->orderBy('id')
                ->get();

            $transactions = [];
            foreach ($movements as $mov) {
                $qty = (float) $mov->qty;
                $totalCost = abs((float) $mov->total_cost);
                $unitCost = $qty != 0 ? $totalCost / abs($qty) : 0;

                $inQty = $mov->type === 'in' ? abs($qty) : 0;
                $outQty = $mov->type === 'out' ? abs($qty) : 0;

                if ($mov->type === 'in') {
                    $runningQty += abs($qty);
                    $runningValue += $totalCost;
                } else {
                    $avgCost = $runningQty > 0 ? $runningValue / $runningQty : 0;
                    $runningQty -= abs($qty);
                    $runningValue -= abs($qty) * $avgCost;
                    $runningValue = max(0, $runningValue);
                }

                $label = match ($mov->ref_type) {
                    'purchases' => 'Pembelian',
                    'invoices' => 'Penjualan',
                    'credit_notes' => 'Retur Penjualan',
                    'debit_notes' => 'Retur Pembelian',
                    'stock_opname' => 'Stock Opname',
                    'journal_void' => 'Pembatalan',
                    'manual' => 'Manual',
                    default => ucfirst($mov->ref_type),
                };

                $transactions[] = [
                    'date' => $mov->date,
                    'document' => $mov->description,
                    'description' => $label,
                    'qty_in' => $inQty,
                    'qty_out' => $outQty,
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalCost,
                    'stock_balance' => $runningQty,
                    'value_balance' => $runningValue,
                    'ref_type' => $mov->ref_type,
                    'ref_id' => $mov->ref_id,
                    'journal_id' => $mov->journal_id,
                ];
            }

            $totalIn = collect($transactions)->sum('qty_in');
            $totalOut = collect($transactions)->sum('qty_out');
            $closingStock = $runningQty;
            $closingValue = $runningValue;

            $result[] = [
                'product' => $product,
                'opening_stock' => $openingStock,
                'opening_value' => $openingValue,
                'closing_stock' => $closingStock,
                'closing_value' => $closingValue,
                'transactions' => $transactions,
            ];
        }

        return [
            'products' => $result,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }

    private static function getArBalanceUpTo(int $contactId, int $arAccountId, string $upToDate): float
    {
        $invoiceTotal = (float) Invoice::where('contact_id', $contactId)
            ->whereIn('status', ['posted', 'partially_paid', 'paid'])
            ->where('date', '<=', $upToDate)
            ->whereNull('deleted_at')
            ->sum('total');

        $invoicePaid = (float) DB::table('payments')
            ->where('payable_type', 'invoices')
            ->whereIn('payable_id', Invoice::where('contact_id', $contactId)->pluck('id'))
            ->where('status', 'verified')
            ->where('date', '<=', $upToDate)
            ->sum('amount');

        $cnTotal = (float) CreditNote::where('contact_id', $contactId)
            ->whereIn('status', ['posted', 'applied'])
            ->where('date', '<=', $upToDate)
            ->sum('total');

        return $invoiceTotal - $invoicePaid - $cnTotal;
    }

    private static function getApBalanceUpTo(int $contactId, int $apAccountId, string $upToDate): float
    {
        $purchaseTotal = (float) Purchase::where('contact_id', $contactId)
            ->whereIn('status', ['posted', 'partially_paid', 'paid'])
            ->where('date', '<=', $upToDate)
            ->whereNull('deleted_at')
            ->sum('total');

        $purchasePaid = (float) DB::table('payments')
            ->where('payable_type', 'purchases')
            ->whereIn('payable_id', Purchase::where('contact_id', $contactId)->pluck('id'))
            ->where('status', 'verified')
            ->where('date', '<=', $upToDate)
            ->sum('amount');

        $dnTotal = (float) DebitNote::where('contact_id', $contactId)
            ->whereIn('status', ['posted', 'applied'])
            ->where('date', '<=', $upToDate)
            ->sum('total');

        return $purchaseTotal - $purchasePaid - $dnTotal;
    }
}
