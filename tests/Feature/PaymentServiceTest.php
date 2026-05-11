<?php

namespace Tests\Feature;

use App\Exceptions\InvalidAccountException;
use App\Exceptions\InvalidOperationException;
use App\Models\Payment;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\PurchaseService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\TestSetup;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase, TestSetup;

    private $customer;
    private $supplier;
    private $wallet;
    private $bank;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestDatabase();

        $this->customer = $this->createContact();
        $this->supplier = $this->createSupplier();
        $this->wallet = $this->createWallet();
        $this->bank = $this->createBankWallet();
    }

    // ==================== createPayment ====================

    public function test_createPayment_for_invoice(): void
    {
        $invoice = InvoiceService::createInvoice([
            'contact_id' => $this->customer->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], [
            ['description' => 'Item', 'qty' => 1, 'unit_price' => 100000],
        ]);
        InvoiceService::postInvoice($invoice);

        $payment = PaymentService::createPayment([
            'payable_type' => 'invoices',
            'payable_id' => $invoice->id,
            'period_id' => $this->periodId(),
            'wallet_id' => $this->wallet->id,
            'date' => $this->today(),
            'amount' => 100000,
            'method' => 'cash',
        ]);

        $this->assertEquals('pending', $payment->status);
        $this->assertEquals(100000, (float) $payment->amount);
    }

    public function test_createPayment_for_purchase(): void
    {
        $purchase = PurchaseService::createPurchase([
            'contact_id' => $this->supplier->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], [
            ['description' => 'Item', 'qty' => 1, 'unit_price' => 50000],
        ]);
        PurchaseService::postPurchase($purchase);

        $payment = PaymentService::createPayment([
            'payable_type' => 'purchases',
            'payable_id' => $purchase->id,
            'period_id' => $this->periodId(),
            'wallet_id' => $this->wallet->id,
            'date' => $this->today(),
            'amount' => 50000,
            'method' => 'cash',
        ]);

        $this->assertEquals('pending', $payment->status);
    }

    public function test_createPayment_exceeds_due_amount_throws(): void
    {
        $invoice = InvoiceService::createInvoice([
            'contact_id' => $this->customer->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], [
            ['description' => 'Item', 'qty' => 1, 'unit_price' => 100000],
        ]);
        InvoiceService::postInvoice($invoice);

        $this->expectException(InvalidOperationException::class);
        PaymentService::createPayment([
            'payable_type' => 'invoices',
            'payable_id' => $invoice->id,
            'period_id' => $this->periodId(),
            'wallet_id' => $this->wallet->id,
            'date' => $this->today(),
            'amount' => 200000,
            'method' => 'cash',
        ]);
    }

    public function test_createPayment_already_paid_throws(): void
    {
        $invoice = InvoiceService::createInvoice([
            'contact_id' => $this->customer->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
            'is_cash_sale' => true,
            'wallet_id' => $this->wallet->id,
        ], [
            ['description' => 'Item', 'qty' => 1, 'unit_price' => 100000],
        ]);
        InvoiceService::postInvoice($invoice);

        $this->expectException(InvalidOperationException::class);
        PaymentService::createPayment([
            'payable_type' => 'invoices',
            'payable_id' => $invoice->id,
            'period_id' => $this->periodId(),
            'wallet_id' => $this->wallet->id,
            'date' => $this->today(),
            'amount' => 50000,
            'method' => 'cash',
        ]);
    }

    public function test_createPayment_invalid_type_throws(): void
    {
        $this->expectException(InvalidAccountException::class);
        PaymentService::createPayment([
            'payable_type' => 'invalid',
            'payable_id' => 1,
            'period_id' => $this->periodId(),
            'wallet_id' => $this->wallet->id,
            'date' => $this->today(),
            'amount' => 1000,
        ]);
    }

    // ==================== verifyPayment ====================

    public function test_verifyPayment_for_invoice(): void
    {
        $invoice = InvoiceService::createInvoice([
            'contact_id' => $this->customer->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], [
            ['description' => 'Item', 'qty' => 1, 'unit_price' => 100000],
        ]);
        InvoiceService::postInvoice($invoice);

        $payment = PaymentService::createPayment([
            'payable_type' => 'invoices',
            'payable_id' => $invoice->id,
            'period_id' => $this->periodId(),
            'wallet_id' => $this->wallet->id,
            'date' => $this->today(),
            'amount' => 100000,
            'method' => 'cash',
        ]);

        $journal = PaymentService::verifyPayment($payment);
        $invoice->refresh();

        $this->assertEquals('verified', $payment->fresh()->status);
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals(100000, (float) $invoice->paid_amount);
        $this->assertNotNull($journal);

        $cashAccountId = $this->getAccountId('1100-00-010');
        $arAccountId = $this->getAccountId('1300-00-020');

        $this->assertEquals(100000, (float) $journal->lines->firstWhere('account_id', $cashAccountId)->debit_amount);
        $this->assertEquals(100000, (float) $journal->lines->firstWhere('account_id', $arAccountId)->credit_amount);
    }

    public function test_verifyPayment_for_purchase(): void
    {
        $purchase = PurchaseService::createPurchase([
            'contact_id' => $this->supplier->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], [
            ['description' => 'Item', 'qty' => 1, 'unit_price' => 50000],
        ]);
        PurchaseService::postPurchase($purchase);

        $payment = PaymentService::createPayment([
            'payable_type' => 'purchases',
            'payable_id' => $purchase->id,
            'period_id' => $this->periodId(),
            'wallet_id' => $this->wallet->id,
            'date' => $this->today(),
            'amount' => 50000,
            'method' => 'cash',
        ]);

        $journal = PaymentService::verifyPayment($payment);
        $purchase->refresh();

        $this->assertEquals('paid', $purchase->status);
        $this->assertEquals(50000, (float) $purchase->paid_amount);

        $apAccountId = $this->getAccountId('2100-00-020');
        $cashAccountId = $this->getAccountId('1100-00-010');

        $this->assertEquals(50000, (float) $journal->lines->firstWhere('account_id', $apAccountId)->debit_amount);
        $this->assertEquals(50000, (float) $journal->lines->firstWhere('account_id', $cashAccountId)->credit_amount);
    }

    public function test_verifyPayment_partial_payment(): void
    {
        $invoice = InvoiceService::createInvoice([
            'contact_id' => $this->customer->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], [
            ['description' => 'Item', 'qty' => 1, 'unit_price' => 100000],
        ]);
        InvoiceService::postInvoice($invoice);

        $payment = PaymentService::createPayment([
            'payable_type' => 'invoices',
            'payable_id' => $invoice->id,
            'period_id' => $this->periodId(),
            'wallet_id' => $this->wallet->id,
            'date' => $this->today(),
            'amount' => 40000,
            'method' => 'cash',
        ]);

        PaymentService::verifyPayment($payment);
        $invoice->refresh();

        $this->assertEquals('partially_paid', $invoice->status);
        $this->assertEquals(40000, (float) $invoice->paid_amount);
        $this->assertEquals(60000, (float) $invoice->due_amount);
    }

    // ==================== cancelPayment ====================

    public function test_cancelPayment(): void
    {
        $invoice = InvoiceService::createInvoice([
            'contact_id' => $this->customer->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], [
            ['description' => 'Item', 'qty' => 1, 'unit_price' => 100000],
        ]);
        InvoiceService::postInvoice($invoice);

        $payment = PaymentService::createPayment([
            'payable_type' => 'invoices',
            'payable_id' => $invoice->id,
            'period_id' => $this->periodId(),
            'wallet_id' => $this->wallet->id,
            'date' => $this->today(),
            'amount' => 100000,
            'method' => 'cash',
        ]);
        PaymentService::verifyPayment($payment);

        PaymentService::cancelPayment($payment);

        $this->assertEquals('cancelled', $payment->fresh()->status);
        $invoice->refresh();
        $this->assertEquals('posted', $invoice->status);
        $this->assertEquals(0, (float) $invoice->paid_amount);
    }

    public function test_cancelPayment_already_cancelled_throws(): void
    {
        $invoice = InvoiceService::createInvoice([
            'contact_id' => $this->customer->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], [
            ['description' => 'Item', 'qty' => 1, 'unit_price' => 100000],
        ]);
        InvoiceService::postInvoice($invoice);

        $payment = PaymentService::createPayment([
            'payable_type' => 'invoices',
            'payable_id' => $invoice->id,
            'period_id' => $this->periodId(),
            'wallet_id' => $this->wallet->id,
            'date' => $this->today(),
            'amount' => 100000,
            'method' => 'cash',
        ]);

        PaymentService::cancelPayment($payment);

        $this->expectException(InvalidAccountException::class);
        PaymentService::cancelPayment($payment);
    }

    public function test_cancelPayment_from_partially_paid_to_posted(): void
    {
        $invoice = InvoiceService::createInvoice([
            'contact_id' => $this->customer->id,
            'period_id' => $this->periodId(),
            'date' => $this->today(),
        ], [
            ['description' => 'Item', 'qty' => 1, 'unit_price' => 100000],
        ]);
        InvoiceService::postInvoice($invoice);

        $payment1 = PaymentService::createPayment([
            'payable_type' => 'invoices',
            'payable_id' => $invoice->id,
            'period_id' => $this->periodId(),
            'wallet_id' => $this->wallet->id,
            'date' => $this->today(),
            'amount' => 100000,
            'method' => 'cash',
        ]);
        $payment2 = PaymentService::createPayment([
            'payable_type' => 'invoices',
            'payable_id' => $invoice->id,
            'period_id' => $this->periodId(),
            'wallet_id' => $this->wallet->id,
            'date' => $this->today(),
            'amount' => 100000,
            'method' => 'cash',
        ]);

        PaymentService::cancelPayment($payment1);

        $p1 = Payment::find($payment1->id);
        $this->assertEquals('cancelled', $p1->status);
    }
}
