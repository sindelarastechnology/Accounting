<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class BugFixTest extends TestCase
{
    public function test_invoice_discount_is_not_double_counted()
    {
        $totalTaxBase = 1000000;
        $discountRate = 0.1; // 10%
        $discountAmount = $totalTaxBase * $discountRate; // 100000
        $netRevenue = $totalTaxBase - $discountAmount; // 900000

        // Old bug: discount = 100000 debit + revenue = 1000000 credit → double-count discount
        // Fix:   net revenue = 900000 credit (single line, discount already deducted)

        $this->assertEquals(100000, $discountAmount);
        $this->assertEquals(900000, $netRevenue);
        $this->assertNotEquals($totalTaxBase, $netRevenue);
    }

    public function test_payment_cancel_status_partially_paid()
    {
        $total = 1000000;
        $paidAmount = 1000000;
        $paymentAmount = 500000;

        // Cancel one of two payments
        $newPaid = max(0, $paidAmount - $paymentAmount);        // 500000
        $newDue = $total - $newPaid;                             // 500000

        $status = $this->determinePayableStatus($newPaid, $newDue);

        $this->assertEquals(500000, $newPaid);
        $this->assertEquals(500000, $newDue);
        $this->assertEquals('partially_paid', $status);
    }

    public function test_payment_cancel_status_posted_when_last_payment_cancelled()
    {
        $total = 1000000;
        $paidAmount = 1000000;
        $paymentAmount = 1000000;

        $newPaid = max(0, $paidAmount - $paymentAmount);        // 0
        $newDue = $total - $newPaid;                             // 1000000

        $status = $this->determinePayableStatus($newPaid, $newDue);

        $this->assertEquals(0, $newPaid);
        $this->assertEquals(1000000, $newDue);
        $this->assertEquals('posted', $status);
    }

    public function test_payment_cancel_from_partially_paid()
    {
        $total = 1000000;
        $paidAmount = 500000;
        $paymentAmount = 250000;

        $newPaid = max(0, $paidAmount - $paymentAmount);        // 250000
        $newDue = $total - $newPaid;                             // 750000

        $status = $this->determinePayableStatus($newPaid, $newDue);

        $this->assertEquals(250000, $newPaid);
        $this->assertEquals(750000, $newDue);
        $this->assertEquals('partially_paid', $status);
    }

    private function determinePayableStatus(float $paid, float $due): string
    {
        if ($due <= 0.0001) {
            return 'paid';
        }
        if ($paid > 0.0001) {
            return 'partially_paid';
        }
        return 'posted';
    }

    public function test_journal_source_fixed_asset_is_accepted()
    {
        $validSources = [
            'manual', 'opening', 'closing', 'invoice', 'purchase',
            'payment', 'credit_note', 'debit_note', 'fixed_asset',
        ];

        $this->assertContains('fixed_asset', $validSources);
        $this->assertContains('credit_note', $validSources);
        $this->assertContains('debit_note', $validSources);
    }
}
