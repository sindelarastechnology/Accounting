<?php

namespace App\Console\Commands;

use App\Helpers\AccountResolver;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\InventoryMovement;
use App\Models\Journal;
use App\Models\JournalLine;
use App\Models\Payment;
use App\Models\Period;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Setting;
use App\Models\TaxRule;
use App\Models\Wallet;
use App\Services\DocumentNumberService;
use App\Services\ExpenseService;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\PurchaseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SimulateAccounting extends Command
{
    protected $signature = 'simulate:accounting {--cleanup : Hapus data simulasi setelah selesai}';
    protected $description = 'Simulasi transaksi akuntansi lengkap untuk verifikasi perhitungan';

    private array $accounts = [];
    private ?Period $period = null;
    private ?Wallet $wallet = null;
    private ?Contact $supplier = null;
    private ?Contact $customer = null;
    private ?Product $product = null;
    private array $results = [];

    public function handle(): int
    {
        $this->info('╔══════════════════════════════════════════╗');
        $this->info('║   SIMULASI AKUNTANSI - VERIFIKASI       ║');
        $this->info('╚══════════════════════════════════════════╝');

        $this->newLine();
        $this->warn('Pastikan database sudah di-seed dengan:');
        $this->warn('  php artisan migrate:fresh --seed');
        $this->newLine();

        if (!$this->confirm('Lanjutkan simulasi?', true)) {
            return Command::FAILURE;
        }

        DB::beginTransaction();

        try {
            $this->step('Memeriksa data master...');
            $this->checkMasterData();

            $this->step('Menyiapkan data master...');
            $this->prepareMasterData();

            // ============================================
            // FLOW 1: Kredit Purchase + Payment
            // ============================================
            $this->section('FLOW 1: PEMBELIAN KREDIT + PEMBAYARAN (PPh 23)');

            $purchaseQty = 10;
            $purchasePrice = 10000;
            $itemSubtotal = $purchaseQty * $purchasePrice;
            $discount = 0;
            $netSubtotal = $itemSubtotal - $discount;
            $ppnRate = 11;
            $ppnAmount = $netSubtotal * $ppnRate / 100;
            $purchaseTotal = $netSubtotal + $ppnAmount;

            // PPh 23 = 2% of DPP (net subtotal, not including PPN)
            $pphRate = 2;
            $pphAmount = $netSubtotal * $pphRate / 100;

            $this->info("Qty: {$purchaseQty}, Price: {$purchasePrice}, Subtotal: {$itemSubtotal}");
            $this->info("PPN {$ppnRate}%: {$ppnAmount}, Total Purchase: {$purchaseTotal}");
            $this->info("PPh 23 {$pphRate}%: {$pphAmount}");

            // Create purchase
            $purchase = PurchaseService::createPurchase(
                [
                    'contact_id' => $this->supplier->id,
                    'period_id' => $this->period->id,
                    'date' => $this->period->start_date->format('Y-m-d'),
                    'due_date' => $this->period->start_date->addDays(30)->format('Y-m-d'),
                    'notes' => 'Simulasi: Pembelian barang',
                ],
                [
                    [
                        'product_id' => $this->product->id,
                        'qty' => $purchaseQty,
                        'unit_price' => $purchasePrice,
                        'description' => $this->product->name,
                    ],
                ],
                [
                    ['tax_rule_id' => $this->getTaxRule('PPN_11')->id],
                ]
            );

            $this->assert("Purchase #{$purchase->number} dibuat", $purchase->status === 'draft');
            $this->assert("Total purchase = {$purchaseTotal}", abs($purchase->total - $purchaseTotal) < 1);

            // Post purchase
            $journalPurchase = PurchaseService::postPurchase($purchase);
            $this->assert("Purchase diposting", $purchase->fresh()->status === 'posted');
            $this->verifyJournalBalanced($journalPurchase, 'Jurnal Purchase');
            $this->verifyJournalLine($journalPurchase, 'Inventory debit', $this->accounts['inventory'], 'debit', $itemSubtotal);
            $this->verifyJournalLine($journalPurchase, 'PPN Input debit', $this->accounts['ppn_input'], 'debit', $ppnAmount);
            $this->verifyJournalLine($journalPurchase, 'AP credit', $this->accounts['ap'], 'credit', $purchaseTotal);

            $stockAfterPurchase = InventoryMovement::where('product_id', $this->product->id)->sum('qty');
            $this->assert("Stock setelah purchase = {$purchaseQty}", abs($stockAfterPurchase - $purchaseQty) < 0.001);

            // Pay the purchase
            $payment = PaymentService::createPayment([
                'payable_type' => 'purchases',
                'payable_id' => $purchase->id,
                'period_id' => $this->period->id,
                'wallet_id' => $this->wallet->id,
                'date' => $this->period->start_date->format('Y-m-d'),
                'amount' => $purchaseTotal,
                'withholding_amount' => $pphAmount,
                'method' => 'transfer',
                'reference' => 'Simulasi pembayaran',
            ]);
            $this->assert("Payment #{$payment->number} dibuat", $payment->status === 'pending');

            $journalPayment = PaymentService::verifyPayment($payment);
            $this->assert("Payment diverifikasi", $payment->fresh()->status === 'verified');
            $this->assert("Purchase status = paid", $purchase->fresh()->status === 'paid');
            $this->verifyJournalBalanced($journalPayment, 'Jurnal Pembayaran Purchase');
            $this->verifyJournalLine($journalPayment, 'AP debit', $this->accounts['ap'], 'debit', $purchaseTotal);
            $netPayment = $purchaseTotal - $pphAmount;
            $this->verifyJournalLine($journalPayment, 'Bank credit', $this->accounts['bank'], 'credit', $netPayment);
            $this->verifyJournalLine($journalPayment, 'PPh Payable credit', $this->accounts['pph_payable'], 'credit', $pphAmount);

            // ============================================
            // FLOW 2: Kredit Sale + Payment
            // ============================================
            $this->section('FLOW 2: PENJUALAN KREDIT + PENERIMAAN PEMBAYARAN');

            $saleQty = 5;
            $salePrice = 15000;
            $saleSubtotal = $saleQty * $salePrice;
            $ppnSale = $saleSubtotal * $ppnRate / 100;
            $saleTotal = $saleSubtotal + $ppnSale;
            $cogsValue = $saleQty * $purchasePrice;

            $this->info("Qty: {$saleQty}, Price: {$salePrice}, Subtotal: {$saleSubtotal}");
            $this->info("PPN: {$ppnSale}, Total: {$saleTotal}, COGS: {$cogsValue}");

            $invoice = InvoiceService::createInvoice(
                [
                    'contact_id' => $this->customer->id,
                    'period_id' => $this->period->id,
                    'date' => $this->period->start_date->format('Y-m-d'),
                    'due_date' => $this->period->start_date->addDays(30)->format('Y-m-d'),
                    'notes' => 'Simulasi: Penjualan barang',
                    'is_cash_sale' => false,
                ],
                [
                    [
                        'product_id' => $this->product->id,
                        'qty' => $saleQty,
                        'unit_price' => $salePrice,
                        'cost_price' => $purchasePrice,
                        'description' => $this->product->name,
                    ],
                ],
                [
                    ['tax_rule_id' => $this->getTaxRule('PPN_11')->id],
                ]
            );

            $this->assert("Invoice #{$invoice->number} dibuat", $invoice->status === 'draft');
            $this->assert("Total invoice = {$saleTotal}", abs($invoice->total - $saleTotal) < 1);

            $journalSale = InvoiceService::postInvoice($invoice);
            $this->assert("Invoice diposting", $invoice->fresh()->status === 'posted');
            $this->verifyJournalBalanced($journalSale, 'Jurnal Penjualan');
            $this->verifyJournalLine($journalSale, 'AR debit', $this->accounts['ar'], 'debit', $saleTotal);
            $this->verifyJournalLine($journalSale, 'Revenue credit', $this->accounts['revenue'], 'credit', $saleSubtotal);
            $this->verifyJournalLine($journalSale, 'PPN Output credit', $this->accounts['ppn_output'], 'credit', $ppnSale);
            $this->verifyJournalLine($journalSale, 'COGS debit', $this->accounts['cogs'], 'debit', $cogsValue);
            $this->verifyJournalLine($journalSale, 'Inventory credit', $this->accounts['inventory'], 'credit', $cogsValue);

            $stockAfterSale = InventoryMovement::where('product_id', $this->product->id)->sum('qty');
            $expectedStock = $purchaseQty - $saleQty;
            $this->assert("Stock setelah penjualan = {$expectedStock}", abs($stockAfterSale - $expectedStock) < 0.001);

            // Receive payment
            $receipt = PaymentService::createPayment([
                'payable_type' => 'invoices',
                'payable_id' => $invoice->id,
                'period_id' => $this->period->id,
                'wallet_id' => $this->wallet->id,
                'date' => $this->period->start_date->format('Y-m-d'),
                'amount' => $saleTotal,
                'withholding_amount' => 0,
                'method' => 'transfer',
                'reference' => 'Simulasi penerimaan',
            ]);
            $this->assert("Receipt #{$receipt->number} dibuat", $receipt->status === 'pending');

            $journalReceipt = PaymentService::verifyPayment($receipt);
            $this->assert("Receipt diverifikasi", $receipt->fresh()->status === 'verified');
            $this->assert("Invoice status = paid", $invoice->fresh()->status === 'paid');
            $this->verifyJournalBalanced($journalReceipt, 'Jurnal Penerimaan');
            $this->verifyJournalLine($journalReceipt, 'Bank debit', $this->accounts['bank'], 'debit', $saleTotal);
            $this->verifyJournalLine($journalReceipt, 'AR credit', $this->accounts['ar'], 'credit', $saleTotal);

            // ============================================
            // FLOW 3: Cash Sale
            // ============================================
            $this->section('FLOW 3: PENJUALAN TUNAI');

            $cashSaleQty = 2;
            $cashSalePrice = 15000;
            $cashSaleSubtotal = $cashSaleQty * $cashSalePrice;
            $ppnCashSale = $cashSaleSubtotal * $ppnRate / 100;
            $cashSaleTotal = $cashSaleSubtotal + $ppnCashSale;
            $cashCogs = $cashSaleQty * $purchasePrice;

            $this->info("Qty: {$cashSaleQty}, Price: {$cashSalePrice}, Subtotal: {$cashSaleSubtotal}");
            $this->info("PPN: {$ppnCashSale}, Total: {$cashSaleTotal}, COGS: {$cashCogs}");

            $cashInvoice = InvoiceService::createInvoice(
                [
                    'contact_id' => $this->customer->id,
                    'period_id' => $this->period->id,
                    'date' => $this->period->start_date->format('Y-m-d'),
                    'due_date' => $this->period->start_date->format('Y-m-d'),
                    'notes' => 'Simulasi: Penjualan tunai',
                    'is_cash_sale' => true,
                    'wallet_id' => $this->wallet->id,
                ],
                [
                    [
                        'product_id' => $this->product->id,
                        'qty' => $cashSaleQty,
                        'unit_price' => $cashSalePrice,
                        'cost_price' => $purchasePrice,
                        'description' => $this->product->name,
                    ],
                ],
                [
                    ['tax_rule_id' => $this->getTaxRule('PPN_11')->id],
                ]
            );

            $this->assert("Cash Invoice #{$cashInvoice->number} dibuat", $cashInvoice->status === 'draft');

            $journalCashSale = InvoiceService::postInvoice($cashInvoice);
            $this->assert("Cash Invoice diposting", $cashInvoice->fresh()->status === 'paid');
            $this->verifyJournalBalanced($journalCashSale, 'Jurnal Penjualan Tunai');
            $this->verifyJournalLine($journalCashSale, 'Bank debit (cash sale)', $this->accounts['bank'], 'debit', $cashSaleTotal);
            $this->verifyJournalLine($journalCashSale, 'Revenue credit (cash sale)', $this->accounts['revenue'], 'credit', $cashSaleSubtotal);
            $this->verifyJournalLine($journalCashSale, 'PPN Output credit (cash sale)', $this->accounts['ppn_output'], 'credit', $ppnCashSale);
            $this->verifyJournalLine($journalCashSale, 'COGS debit (cash sale)', $this->accounts['cogs'], 'debit', $cashCogs);
            $this->verifyJournalLine($journalCashSale, 'Inventory credit (cash sale)', $this->accounts['inventory'], 'credit', $cashCogs);

            $stockAfterCashSale = InventoryMovement::where('product_id', $this->product->id)->sum('qty');
            $expectedFinalStock = $purchaseQty - $saleQty - $cashSaleQty;
            $this->assert("Stock setelah semua transaksi = {$expectedFinalStock}", abs($stockAfterCashSale - $expectedFinalStock) < 0.001);

            // ============================================
            // FLOW 4: Direct Expense with PPN
            // ============================================
            $this->section('FLOW 4: BEBAN LANGSUNG (TERMASUK PPN)');

            $expenseAmount = 55000;
            $dpp = round($expenseAmount / 1.11, 2);
            $ppnExpense = round($expenseAmount - $dpp, 2);

            $this->info("Amount: {$expenseAmount}, DPP: {$dpp}, PPN: {$ppnExpense}");

            $expenseData = [
                'contact_id' => $this->supplier->id,
                'period_id' => $this->period->id,
                'wallet_id' => $this->wallet->id,
                'date' => $this->period->start_date->format('Y-m-d'),
                'account_id' => AccountResolver::expense(),
                'amount' => $expenseAmount,
                'include_tax' => true,
                'name' => 'Simulasi: Beban listrik termasuk PPN',
            ];

            $expense = ExpenseService::createExpense($expenseData);

            $this->assert("Expense #{$expense->number} dibuat", $expense->status === 'draft');

            $journalExpense = ExpenseService::postExpense($expense);
            $this->assert("Expense diposting", $expense->fresh()->status === 'posted');
            $this->verifyJournalBalanced($journalExpense, 'Jurnal Beban');
            $this->verifyJournalLine($journalExpense, 'Expense debit', $this->accounts['expense'], 'debit', $dpp);
            $this->verifyJournalLine($journalExpense, 'PPN Input debit (expense)', $this->accounts['ppn_input'], 'debit', $ppnExpense);
            $this->verifyJournalLine($journalExpense, 'Bank credit (expense)', $this->accounts['bank'], 'credit', $expenseAmount);

            // ============================================
            // FINAL ACCOUNT BALANCE VERIFICATION
            // ============================================
            $this->section('VERIFIKASI SALDO AKHIR');

            $this->checkAccountBalance('1100-00-020', 'Kas', $this->getAccountBalance('1100-00-020'));
            $this->checkAccountBalance('1200-00-010', 'Bank BCA', $this->getAccountBalance('1200-00-010'));
            $this->checkAccountBalance('1300-00-020', 'Piutang Usaha (AR)', $this->getAccountBalance('1300-00-020'));
            $this->checkAccountBalance('1400-00-010', 'Persediaan', $this->getAccountBalance('1400-00-010'));
            $this->checkAccountBalance('1500-00-030', 'PPN Masukan', $this->getAccountBalance('1500-00-030'));
            $this->checkAccountBalance('2100-00-020', 'Hutang Usaha (AP)', $this->getAccountBalance('2100-00-020'));
            $this->checkAccountBalance('2100-00-070', 'PPN Keluaran', $this->getAccountBalance('2100-00-070'));
            $this->checkAccountBalance('2100-00-071', 'Hutang PPh 23', $this->getAccountBalance('2100-00-071'));
            $this->checkAccountBalance('4100-00-010', 'Pendapatan', $this->getAccountBalance('4100-00-010'));
            $this->checkAccountBalance('5100-00-010', 'HPP', $this->getAccountBalance('5100-00-010'));
            $this->checkAccountBalance('6110-00-010', 'Beban', $this->getAccountBalance('6110-00-010'));

            // ============================================
            // Summary
            // ============================================
            $this->table(
                ['#', 'Transaksi', 'Status', 'Detail'],
                $this->results
            );

            $passed = collect($this->results)->where('2', '✅')->count();
            $total = count($this->results);
            $failed = $total - $passed;

            $this->newLine();
            $this->info("Total: {$total} assertions | ✅ Lolos: {$passed} | ❌ Gagal: {$failed}");

            if ($failed > 0) {
                $this->error('❌ BEBERAPA ASSERTION GAGAL! Periksa detail di atas.');
            } else {
                $this->info('✅ SEMUA ASSERTION LULUS! Sistem akuntansi berjalan dengan benar.');
            }

            if ($this->option('cleanup')) {
                $this->warn('Rollback transaksi simulasi...');
                DB::rollBack();
                $this->info('Data simulasi telah dihapus (rollback).');
            } else {
                DB::commit();
                if ($failed > 0) {
                    return Command::FAILURE;
                }
            }

            return $failed > 0 ? Command::FAILURE : Command::SUCCESS;

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('❌ ERROR: ' . $e->getMessage());
            $this->error('File: ' . $e->getFile() . ':' . $e->getLine());
            $this->error($e->getTraceAsString());

            $this->newLine();
            $this->table(
                ['#', 'Transaksi', 'Status', 'Detail'],
                $this->results
            );
            return Command::FAILURE;
        }
    }

    private function step(string $msg): void
    {
        $this->newLine();
        $this->line("  🞄  {$msg}");
    }

    private function section(string $title): void
    {
        $this->newLine();
        $this->line(str_repeat('─', 60));
        $this->info("  {$title}");
        $this->line(str_repeat('─', 60));
    }

    private function assert(string $description, bool $condition): void
    {
        $status = $condition ? '✅' : '❌';
        $this->results[] = [count($this->results) + 1, $description, $status, $condition ? '' : 'GAGAL'];
        if (!$condition) {
            $this->error("  ❌ {$description}");
        }
    }

    private function checkMasterData(): void
    {
        // Verify seeded data exists
        $periodCount = Period::count();
        $this->assert("Periode tersedia: {$periodCount}", $periodCount > 0);

        $accountCount = Account::count();
        $this->assert("Akun tersedia: {$accountCount}", $accountCount >= 120);

        $user = \App\Models\User::where('email', 'admin@example.com')->first();
        $this->assert("User admin tersedia", (bool) $user);

        // Verify tax rules
        $ppnRule = TaxRule::where('code', 'PPN_11')->first();
        $pphRule = TaxRule::where('code', 'PPH23_2')->first();
        $this->assert("Tax rule PPN_11 ada", (bool) $ppnRule);
        $this->assert("Tax rule PPH23_2 ada", (bool) $pphRule);

        // Verify accounts
        $this->accounts['inventory'] = $this->getAccountId('1400-00-010');
        $this->accounts['ar'] = $this->getAccountId('1300-00-020');
        $this->accounts['ap'] = $this->getAccountId('2100-00-020');
        $this->accounts['revenue'] = $this->getAccountId('4100-00-010');
        $this->accounts['cogs'] = $this->getAccountId('5100-00-010');
        $this->accounts['ppn_input'] = $this->getAccountId('1500-00-030');
        $this->accounts['ppn_output'] = $this->getAccountId('2100-00-070');
        $this->accounts['pph_payable'] = $this->getAccountId('2100-00-071');
        $this->accounts['expense'] = $this->getAccountId('6110-00-010');

        foreach ($this->accounts as $name => $id) {
            $this->assert("Akun {$name} (ID: {$id}) tersedia", $id > 0);
        }
    }

    private function prepareMasterData(): void
    {
        // Get open period
        $this->period = Period::where('is_closed', false)->orderBy('start_date')->first();
        if (!$this->period) {
            throw new \RuntimeException('Tidak ada periode terbuka.');
        }

        // Create or get wallet
        $bankAccount = Account::where('code', '1200-00-010')->first();
        if (!$bankAccount) {
            throw new \RuntimeException('Akun Bank BCA (1200-00-010) tidak ditemukan.');
        }
        $this->accounts['bank'] = $bankAccount->id;

        $this->wallet = Wallet::firstOrCreate(
            ['name' => 'Bank BCA Simulasi'],
            [
                'type' => 'bank',
                'bank_name' => 'BCA',
                'account_number' => '1234567890',
                'account_holder' => 'PT Onezie',
                'account_id' => $bankAccount->id,
                'opening_balance' => 100000000,
                'is_active' => true,
            ]
        );

        // Create supplier contact
        $this->supplier = Contact::firstOrCreate(
            ['code' => 'SUP-SIMULASI'],
            [
                'name' => 'Supplier Simulasi',
                'type' => 'supplier',
                'is_active' => true,
            ]
        );

        // Create customer contact
        $this->customer = Contact::firstOrCreate(
            ['code' => 'CUS-SIMULASI'],
            [
                'name' => 'Customer Simulasi',
                'type' => 'customer',
                'is_active' => true,
            ]
        );

        // Create goods product
        $this->product = Product::firstOrCreate(
            ['code' => 'BRG-SIM-001'],
            [
                'name' => 'Barang Simulasi',
                'type' => 'goods',
                'unit' => 'pcs',
                'purchase_price' => 10000,
                'selling_price' => 15000,
                'stock_on_hand' => 0,
                'is_active' => true,
                'cogs_account_id' => $this->accounts['cogs'],
                'inventory_account_id' => $this->accounts['inventory'],
                'revenue_account_id' => $this->accounts['revenue'],
            ]
        );
    }

    private function getAccountId(string $code): int
    {
        $account = Account::where('code', $code)->first();
        if (!$account) {
            throw new \RuntimeException("Akun dengan kode '{$code}' tidak ditemukan.");
        }
        return $account->id;
    }

    private function getTaxRule(string $code): TaxRule
    {
        $rule = TaxRule::where('code', $code)->first();
        if (!$rule) {
            throw new \RuntimeException("Tax rule '{$code}' tidak ditemukan.");
        }
        return $rule;
    }

    private function verifyJournalBalanced(Journal $journal, string $label): void
    {
        $totalDebit = $journal->lines()->sum('debit_amount');
        $totalCredit = $journal->lines()->sum('credit_amount');
        $diff = abs($totalDebit - $totalCredit);
        $this->assert("{$label}: Balance (Debit={$totalDebit}, Credit={$totalCredit})", $diff < 1);
    }

    private function verifyJournalLine(Journal $journal, string $label, int $accountId, string $type, float $expectedAmount): void
    {
        $lines = $journal->lines()->where('account_id', $accountId)->get();
        $actualAmount = $type === 'debit'
            ? round($lines->sum('debit_amount'), 2)
            : round($lines->sum('credit_amount'), 2);

        $pass = abs($actualAmount - round($expectedAmount, 2)) < 1;
        $this->assert(
            "{$label}: expected={$expectedAmount}, actual={$actualAmount}",
            $pass
        );
    }

    private function getAccountBalance(string $code): float
    {
        $account = Account::where('code', $code)->first();
        if (!$account) return 0;

        $totalDebit = JournalLine::whereHas('journal', function ($q) {
                $q->where('type', '!=', 'void');
            })
            ->where('account_id', $account->id)
            ->sum('debit_amount');

        $totalCredit = JournalLine::whereHas('journal', function ($q) {
                $q->where('type', '!=', 'void');
            })
            ->where('account_id', $account->id)
            ->sum('credit_amount');

        $balance = $account->normal_balance === 'debit'
            ? $totalDebit - $totalCredit
            : $totalCredit - $totalDebit;

        return round($balance, 2);
    }

    private function checkAccountBalance(string $code, string $label, float $balance): void
    {
        $this->line("  {$label} ({$code}): {$balance}");
    }
}
