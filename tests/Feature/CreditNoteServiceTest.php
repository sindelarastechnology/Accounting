<?php

namespace Tests\Feature;

use App\Exceptions\InvalidAccountException;
use App\Models\TaxRule;
use App\Services\CreditNoteService;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\TestSetup;
use Tests\TestCase;

class CreditNoteServiceTest extends TestCase
{
    use RefreshDatabase, TestSetup;

    private $customer;
    private $product;
    private $taxPpn;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestDatabase();

        $this->customer = $this->createContact();
        $this->product = $this->createProduct();
        $this->taxPpn = TaxRule::where('code', 'PPN_11')->first();
    }

    public function test_createCreditNote_creates_draft(): void
    {
        $cn = CreditNoteService::createCreditNote([
            'contact_id' => $this->customer->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], [
            ['description' => 'Retur item', 'qty' => 1, 'unit_price' => 50000],
        ]);

        $this->assertEquals('draft', $cn->status);
        $this->assertEquals(50000, (float) $cn->total);
    }

    public function test_createCreditNote_with_non_customer_throws(): void
    {
        $supplier = $this->createSupplier();

        $this->expectException(InvalidAccountException::class);
        CreditNoteService::createCreditNote([
            'contact_id' => $supplier->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], [
            ['description' => 'Item', 'qty' => 1, 'unit_price' => 50000],
        ]);
    }

    public function test_postCreditNote_creates_journal(): void
    {
        $invoice = InvoiceService::createInvoice([
            'contact_id' => $this->customer->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], [
            ['description' => 'Item', 'qty' => 1, 'unit_price' => 100000],
        ]);
        InvoiceService::postInvoice($invoice);

        $cn = CreditNoteService::createCreditNote([
            'contact_id' => $this->customer->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
            'invoice_id' => $invoice->id,
        ], [
            ['description' => 'Retur', 'qty' => 1, 'unit_price' => 100000],
        ], [
            ['tax_rule_id' => $this->taxPpn->id, 'method' => 'exclusive', 'rate' => 11],
        ]);

        CreditNoteService::postCreditNote($cn);
        $cn->refresh();

        $this->assertEquals('posted', $cn->status);
        $this->assertNotNull($cn->journal_id);
    }

    public function test_cancelCreditNote(): void
    {
        $cn = CreditNoteService::createCreditNote([
            'contact_id' => $this->customer->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], [
            ['description' => 'Retur', 'qty' => 1, 'unit_price' => 50000],
        ]);
        CreditNoteService::postCreditNote($cn);

        CreditNoteService::cancelCreditNote($cn);
        $cn->refresh();

        $this->assertEquals('cancelled', $cn->status);
    }

    public function test_applyCreditNoteToInvoice(): void
    {
        $invoice = InvoiceService::createInvoice([
            'contact_id' => $this->customer->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], [
            ['description' => 'Item', 'qty' => 1, 'unit_price' => 100000],
        ]);
        InvoiceService::postInvoice($invoice);

        $cn = CreditNoteService::createCreditNote([
            'contact_id' => $this->customer->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
            'invoice_id' => $invoice->id,
        ], [
            ['description' => 'Retur', 'qty' => 1, 'unit_price' => 50000],
        ]);
        CreditNoteService::postCreditNote($cn);

        CreditNoteService::applyCreditNoteToInvoice($cn, $invoice);
        $invoice->refresh();
        $cn->refresh();

        $this->assertEquals('partially_paid', $invoice->status);
        $this->assertEquals(50000, (float) $invoice->paid_amount);
        $this->assertEquals(50000, (float) $invoice->due_amount);
    }
}
