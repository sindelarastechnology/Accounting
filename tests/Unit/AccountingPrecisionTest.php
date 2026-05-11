<?php

namespace Tests\Unit;

use App\Support\AccountingPrecision;
use PHPUnit\Framework\TestCase;

class AccountingPrecisionTest extends TestCase
{
    public function test_format_returns_4_decimal_places(): void
    {
        $this->assertEquals('1000.0000', AccountingPrecision::format(1000));
        $this->assertEquals('1000.5000', AccountingPrecision::format(1000.5));
        $this->assertEquals('1000.1234', AccountingPrecision::format(1000.1234));
        $this->assertEquals('0.0000', AccountingPrecision::format(0));
        $this->assertEquals('-100.0000', AccountingPrecision::format(-100));
    }

    public function test_compare_equal_values(): void
    {
        $this->assertEquals(0, AccountingPrecision::compare(1000.00, 1000.00));
        $this->assertEquals(0, AccountingPrecision::compare(0.0001, 0.0001));
        $this->assertEquals(0, AccountingPrecision::compare(1000000.1234, 1000000.1234));
    }

    public function test_compare_a_greater_than_b(): void
    {
        $this->assertEquals(1, AccountingPrecision::compare(1000.01, 1000.00));
        $this->assertEquals(1, AccountingPrecision::compare(0.0002, 0.0001));
    }

    public function test_compare_a_less_than_b(): void
    {
        $this->assertEquals(-1, AccountingPrecision::compare(1000.00, 1000.01));
        $this->assertEquals(-1, AccountingPrecision::compare(0.0001, 0.0002));
    }

    public function test_compare_handles_rounding_errors(): void
    {
        // Classic floating point issue: 0.1 + 0.2 !== 0.3
        $sum = 0.1 + 0.2;
        $this->assertEquals(0, AccountingPrecision::compare($sum, 0.3));
    }

    public function test_compare_with_large_numbers(): void
    {
        $this->assertEquals(0, AccountingPrecision::compare(1000000000.1234, 1000000000.1234));
        $this->assertEquals(1, AccountingPrecision::compare(1000000000.1235, 1000000000.1234));
    }
}
