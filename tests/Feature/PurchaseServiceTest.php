<?php

namespace Tests\Feature;

use App\Exceptions\InvalidAccountException;
use App\Exceptions\InvalidStateException;
use App\Models\TaxRule;
use App\Services\PurchaseService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\TestSetup;
use Tests\TestCase;

class PurchaseServiceTest extends TestCase
{
    use RefreshDatabase, TestSetup;

    private $supplier;
    private $product;
    private $taxPpn;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestDatabase();

        $this->supplier = $this->createSupplier();
        $this->product = $this->createProduct();
        $this->taxPpn = TaxRule::where('code', 'PPN_11')->first();
    }

    // ==================== createPurchase ====================

    public function test_createPurchase_with_empty_items_throws(): void
    {
        $this->expectException(InvalidAccountException::class);
        PurchaseService::createPurchase([
            'contact_id' => $this->supplier->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], []);
    }

    public function test_createPurchase_with_non_supplier_throws(): void
    {
        $customer = $this->createContact();

        $this->expectException(InvalidAccountException::class);
        PurchaseService::createPurchase([
            'contact_id' => $customer->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], [
            ['description' => 'Item 1', 'qty' => 1, 'unit_price' => 50000],
        ]);
    }

    public function test_createPurchase_creates_draft(): void
    {
        $purchase = PurchaseService::createPurchase([
            'contact_id' => $this->supplier->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
            'due_date' => Carbon::now()->addDays(30)->format('Y-m-d'),
        ], [
            ['description' => 'Item 1', 'qty' => 2, 'unit_price' => 50000],
        ]);

        $this->assertEquals('draft', $purchase->status);
        $this->assertEquals(100000, (float) $purchase->total);
        $this->assertEquals(100000, (float) $purchase->due_amount);
    }

    public function test_createPurchase_goods_uses_inventory_account(): void
    {
        $purchase = PurchaseService::createPurchase([
            'contact_id' => $this->supplier->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], [
            ['product_id' => $this->product->id, 'qty' => 1, 'unit_price' => 50000],
        ]);

        $item = $purchase->items->first();
        $inventoryAccountId = $this->getAccountId('1400-00-010');
        $this->assertEquals($inventoryAccountId, $item->account_id);
    }

    public function test_createPurchase_with_tax_exclusive(): void
    {
        $purchase = PurchaseService::createPurchase([
            'contact_id' => $this->supplier->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], [
            ['description' => 'Item 1', 'qty' => 1, 'unit_price' => 100000],
        ], [
            ['tax_rule_id' => $this->taxPpn->id, 'method' => 'exclusive', 'rate' => 11],
        ]);

        $this->assertEquals(111000, (float) $purchase->total);
        $this->assertEquals(11000, (float) $purchase->tax_amount);
    }

    // ==================== postPurchase ====================

    public function test_postPurchase_creates_journal(): void
    {
        $purchase = PurchaseService::createPurchase([
            'contact_id' => $this->supplier->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], [
            ['description' => 'Item 1', 'qty' => 1, 'unit_price' => 100000],
        ], [
            ['tax_rule_id' => $this->taxPpn->id, 'method' => 'exclusive', 'rate' => 11],
        ]);

        $journal = PurchaseService::postPurchase($purchase);

        $this->assertNotNull($journal);

        $apAccountId = $this->getAccountId('2100-00-020');
        $expenseAccountId = $this->getAccountId('6110-00-010');
        $ppnInputId = $this->getAccountId('1500-00-030');

        $apLine = $journal->lines->firstWhere('account_id', $apAccountId);
        $this->assertEquals(111000, (float) $apLine->credit_amount);

        $expenseLine = $journal->lines->firstWhere('account_id', $expenseAccountId);
        $this->assertEquals(100000, (float) $expenseLine->debit_amount);

        $ppnLine = $journal->lines->firstWhere('account_id', $ppnInputId);
        $this->assertEquals(11000, (float) $ppnLine->debit_amount);
    }

    public function test_postPurchase_updates_status(): void
    {
        $purchase = PurchaseService::createPurchase([
            'contact_id' => $this->supplier->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], [
            ['description' => 'Item 1', 'qty' => 1, 'unit_price' => 50000],
        ]);

        PurchaseService::postPurchase($purchase);
        $purchase->refresh();

        $this->assertEquals('posted', $purchase->status);
        $this->assertNotNull($purchase->journal_id);
    }

    public function test_postPurchase_cannot_post_twice(): void
    {
        $purchase = PurchaseService::createPurchase([
            'contact_id' => $this->supplier->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], [
            ['description' => 'Item 1', 'qty' => 1, 'unit_price' => 50000],
        ]);

        PurchaseService::postPurchase($purchase);

        $this->expectException(InvalidStateException::class);
        PurchaseService::postPurchase($purchase);
    }

    // ==================== cancelPurchase ====================

    public function test_cancelPurchase(): void
    {
        $purchase = PurchaseService::createPurchase([
            'contact_id' => $this->supplier->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], [
            ['description' => 'Item 1', 'qty' => 1, 'unit_price' => 50000],
        ]);

        PurchaseService::postPurchase($purchase);
        PurchaseService::cancelPurchase($purchase);

        $purchase->refresh();
        $this->assertEquals('cancelled', $purchase->status);
    }

    public function test_cancelPurchase_already_cancelled_throws(): void
    {
        $purchase = PurchaseService::createPurchase([
            'contact_id' => $this->supplier->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], [
            ['description' => 'Item 1', 'qty' => 1, 'unit_price' => 50000],
        ]);

        PurchaseService::cancelPurchase($purchase);

        $this->expectException(InvalidStateException::class);
        PurchaseService::cancelPurchase($purchase);
    }
}
