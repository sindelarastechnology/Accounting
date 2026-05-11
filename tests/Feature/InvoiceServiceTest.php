<?php

namespace Tests\Feature;

use App\Exceptions\InvalidAccountException;
use App\Exceptions\InvalidStateException;
use App\Models\Period;
use App\Models\TaxRule;
use App\Services\InvoiceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\TestSetup;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase, TestSetup;

    private $contact;
    private $product;
    private $serviceProduct;
    private $taxPpn;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestDatabase();

        $this->contact = $this->createContact();
        $this->product = $this->createProduct();
        $this->serviceProduct = $this->createServiceProduct();
        $this->taxPpn = TaxRule::where('code', 'PPN_11')->first();
    }

    // ==================== createInvoice ====================

    public function test_createInvoice_with_empty_items_throws(): void
    {
        $this->expectException(InvalidAccountException::class);
        InvoiceService::createInvoice([
            'contact_id' => $this->contact->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], []);
    }

    public function test_createInvoice_with_non_customer_contact_throws(): void
    {
        $supplier = $this->createSupplier();

        $this->expectException(InvalidAccountException::class);
        InvoiceService::createInvoice([
            'contact_id' => $supplier->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], [
            ['description' => 'Item 1', 'qty' => 1, 'unit_price' => 100000],
        ]);
    }

    public function test_createInvoice_creates_draft_invoice(): void
    {
        $invoice = InvoiceService::createInvoice([
            'contact_id' => $this->contact->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
            'due_date' => Carbon::now()->addDays(30)->format('Y-m-d'),
        ], [
            ['description' => 'Item 1', 'qty' => 2, 'unit_price' => 100000],
        ]);

        $this->assertEquals('draft', $invoice->status);
        $this->assertEquals(200000, (float) $invoice->total);
        $this->assertEquals(200000, (float) $invoice->due_amount);
        $this->assertCount(1, $invoice->items);
    }

    public function test_createInvoice_calculates_tax_exclusive(): void
    {
        $invoice = InvoiceService::createInvoice([
            'contact_id' => $this->contact->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], [
            ['description' => 'Item 1', 'qty' => 1, 'unit_price' => 100000],
        ], [
            ['tax_rule_id' => $this->taxPpn->id, 'method' => 'exclusive', 'rate' => 11],
        ]);

        $this->assertEquals(111000, (float) $invoice->total);
        $this->assertEquals(11000, (float) $invoice->tax_amount);
    }

    public function test_createInvoice_with_item_discount_percent(): void
    {
        $invoice = InvoiceService::createInvoice([
            'contact_id' => $this->contact->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], [
            ['description' => 'Item 1', 'qty' => 10, 'unit_price' => 10000, 'discount_percent' => 10],
        ]);

        $this->assertEquals(90000, (float) $invoice->subtotal);
        $this->assertEquals(10000, (float) $invoice->items->first()->discount_amount);
    }

    public function test_createInvoice_with_global_discount(): void
    {
        $invoice = InvoiceService::createInvoice([
            'contact_id' => $this->contact->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
            'discount_amount' => 50000,
        ], [
            ['description' => 'Item 1', 'qty' => 1, 'unit_price' => 100000],
            ['description' => 'Item 2', 'qty' => 1, 'unit_price' => 100000],
        ]);

        $this->assertEquals(200000, (float) $invoice->subtotal);
        $this->assertEquals(150000, (float) $invoice->total);
    }

    public function test_createInvoice_cash_sale_requires_wallet(): void
    {
        $wallet = $this->createWallet();

        $invoice = InvoiceService::createInvoice([
            'contact_id' => $this->contact->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
            'is_cash_sale' => true,
            'wallet_id' => $wallet->id,
        ], [
            ['description' => 'Item 1', 'qty' => 1, 'unit_price' => 100000],
        ]);

        $this->assertTrue($invoice->is_cash_sale);
        $this->assertEquals($wallet->id, $invoice->wallet_id);
    }

    public function test_createInvoice_with_product(): void
    {
        $invoice = InvoiceService::createInvoice([
            'contact_id' => $this->contact->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], [
            ['product_id' => $this->product->id, 'qty' => 1, 'unit_price' => 100000],
        ]);

        $this->assertEquals($this->product->id, $invoice->items->first()->product_id);
        $this->assertEquals($this->product->name, $invoice->items->first()->description);
    }

    // ==================== postInvoice (credit sale) ====================

    public function test_postInvoice_credit_sale_creates_journal(): void
    {
        $invoice = InvoiceService::createInvoice([
            'contact_id' => $this->contact->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], [
            ['description' => 'Item 1', 'qty' => 1, 'unit_price' => 100000],
        ], [
            ['tax_rule_id' => $this->taxPpn->id, 'method' => 'exclusive', 'rate' => 11],
        ]);

        $journal = InvoiceService::postInvoice($invoice);

        $this->assertNotNull($journal);
        $this->assertEquals(3, $journal->lines->count());

        $arAccountId = $this->getAccountId('1300-00-020');
        $revenueAccountId = $this->getAccountId('4100-00-010');
        $ppnOutputId = $this->getAccountId('2100-00-070');

        $arLine = $journal->lines->firstWhere('account_id', $arAccountId);
        $this->assertEquals(111000, (float) $arLine->debit_amount);

        $revLine = $journal->lines->firstWhere('account_id', $revenueAccountId);
        $this->assertEquals(100000, (float) $revLine->credit_amount);

        $ppnLine = $journal->lines->firstWhere('account_id', $ppnOutputId);
        $this->assertEquals(11000, (float) $ppnLine->credit_amount);
    }

    public function test_postInvoice_update_status(): void
    {
        $invoice = InvoiceService::createInvoice([
            'contact_id' => $this->contact->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], [
            ['description' => 'Item 1', 'qty' => 1, 'unit_price' => 100000],
        ]);

        InvoiceService::postInvoice($invoice);
        $invoice->refresh();

        $this->assertEquals('posted', $invoice->status);
        $this->assertNotNull($invoice->journal_id);
    }

    public function test_postInvoice_cannot_post_twice(): void
    {
        $invoice = InvoiceService::createInvoice([
            'contact_id' => $this->contact->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], [
            ['description' => 'Item 1', 'qty' => 1, 'unit_price' => 100000],
        ]);

        $this->expectException(InvalidStateException::class);
        InvoiceService::postInvoice($invoice);
        InvoiceService::postInvoice($invoice);
    }

    public function test_postInvoice_cannot_post_cancelled(): void
    {
        $invoice = InvoiceService::createInvoice([
            'contact_id' => $this->contact->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], [
            ['description' => 'Item 1', 'qty' => 1, 'unit_price' => 100000],
        ]);

        InvoiceService::cancelInvoice($invoice, 'test');

        $this->expectException(InvalidStateException::class);
        InvoiceService::postInvoice($invoice);
    }

    // ==================== postCashSaleInvoice ====================

    public function test_postInvoice_cash_sale_creates_journal_and_payment(): void
    {
        $wallet = $this->createWallet();
        $this->contact->type = 'customer';
        $this->contact->save();

        $invoice = InvoiceService::createInvoice([
            'contact_id' => $this->contact->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
            'is_cash_sale' => true,
            'wallet_id' => $wallet->id,
        ], [
            ['description' => 'Item 1', 'qty' => 1, 'unit_price' => 100000],
        ]);

        $journal = InvoiceService::postInvoice($invoice);
        $invoice->refresh();

        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals(100000, (float) $invoice->paid_amount);
        $this->assertEquals(0, (float) $invoice->due_amount);

        $this->assertDatabaseHas('payments', [
            'payable_type' => 'invoices',
            'payable_id' => $invoice->id,
            'status' => 'verified',
        ]);
    }

    // ==================== cancelInvoice ====================

    public function test_cancelInvoice_credit_sale(): void
    {
        $invoice = InvoiceService::createInvoice([
            'contact_id' => $this->contact->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], [
            ['description' => 'Item 1', 'qty' => 1, 'unit_price' => 100000],
        ]);

        InvoiceService::postInvoice($invoice);
        InvoiceService::cancelInvoice($invoice, 'Test cancel');

        $invoice->refresh();
        $this->assertEquals('cancelled', $invoice->status);
        $this->assertEquals(0, (float) $invoice->paid_amount);
        $this->assertEquals(0, (float) $invoice->due_amount);
    }

    public function test_cancelInvoice_cash_sale_succeeds(): void
    {
        $wallet = $this->createWallet();

        $invoice = InvoiceService::createInvoice([
            'contact_id' => $this->contact->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
            'is_cash_sale' => true,
            'wallet_id' => $wallet->id,
        ], [
            ['description' => 'Item 1', 'qty' => 1, 'unit_price' => 100000],
        ]);

        InvoiceService::postInvoice($invoice);
        InvoiceService::cancelInvoice($invoice, 'test');

        $invoice->refresh();
        $this->assertEquals('cancelled', $invoice->status);
    }

    public function test_cancelInvoice_cancelled_throws(): void
    {
        $invoice = InvoiceService::createInvoice([
            'contact_id' => $this->contact->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], [
            ['description' => 'Item 1', 'qty' => 1, 'unit_price' => 100000],
        ]);

        InvoiceService::cancelInvoice($invoice, 'test');

        $this->expectException(InvalidStateException::class);
        InvoiceService::cancelInvoice($invoice, 'again');
    }
}
