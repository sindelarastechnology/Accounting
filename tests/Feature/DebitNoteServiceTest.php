<?php

namespace Tests\Feature;

use App\Exceptions\InvalidAccountException;
use App\Models\TaxRule;
use App\Services\DebitNoteService;
use App\Services\PurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\TestSetup;
use Tests\TestCase;

class DebitNoteServiceTest extends TestCase
{
    use RefreshDatabase, TestSetup;

    private $supplier;
    private $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestDatabase();

        $this->supplier = $this->createSupplier();
        $this->product = $this->createProduct();
    }

    public function test_createDebitNote_creates_draft(): void
    {
        $dn = DebitNoteService::createDebitNote([
            'contact_id' => $this->supplier->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], [
            ['description' => 'Retur barang', 'qty' => 1, 'unit_price' => 50000],
        ]);

        $this->assertEquals('draft', $dn->status);
        $this->assertEquals(50000, (float) $dn->total);
    }

    public function test_createDebitNote_with_non_supplier_throws(): void
    {
        $customer = $this->createContact();

        $this->expectException(InvalidAccountException::class);
        DebitNoteService::createDebitNote([
            'contact_id' => $customer->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], [
            ['description' => 'Item', 'qty' => 1, 'unit_price' => 50000],
        ]);
    }

    public function test_postDebitNote_creates_journal(): void
    {
        $purchase = PurchaseService::createPurchase([
            'contact_id' => $this->supplier->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], [
            ['description' => 'Item', 'qty' => 1, 'unit_price' => 100000],
        ]);
        PurchaseService::postPurchase($purchase);

        $dn = DebitNoteService::createDebitNote([
            'contact_id' => $this->supplier->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
            'purchase_id' => $purchase->id,
        ], [
            ['description' => 'Retur', 'qty' => 1, 'unit_price' => 100000],
        ]);

        DebitNoteService::postDebitNote($dn);
        $dn->refresh();

        $this->assertEquals('posted', $dn->status);
        $this->assertNotNull($dn->journal_id);
    }

    public function test_cancelDebitNote(): void
    {
        $purchase = PurchaseService::createPurchase([
            'contact_id' => $this->supplier->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], [
            ['description' => 'Item', 'qty' => 1, 'unit_price' => 100000],
        ]);
        PurchaseService::postPurchase($purchase);

        $dn = DebitNoteService::createDebitNote([
            'contact_id' => $this->supplier->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], [
            ['description' => 'Retur', 'qty' => 1, 'unit_price' => 50000],
        ]);
        DebitNoteService::postDebitNote($dn);

        DebitNoteService::cancelDebitNote($dn);
        $dn->refresh();

        $this->assertEquals('cancelled', $dn->status);
    }

    public function test_applyDebitNoteToPurchase(): void
    {
        $purchase = PurchaseService::createPurchase([
            'contact_id' => $this->supplier->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], [
            ['description' => 'Item', 'qty' => 1, 'unit_price' => 100000],
        ]);
        PurchaseService::postPurchase($purchase);

        $dn = DebitNoteService::createDebitNote([
            'contact_id' => $this->supplier->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
            'purchase_id' => $purchase->id,
        ], [
            ['description' => 'Retur', 'qty' => 1, 'unit_price' => 50000],
        ]);
        DebitNoteService::postDebitNote($dn);

        DebitNoteService::applyDebitNoteToPurchase($dn, $purchase);
        $purchase->refresh();
        $dn->refresh();

        $this->assertEquals('partially_paid', $purchase->status);
        $this->assertEquals(50000, (float) $purchase->paid_amount);
    }
}
