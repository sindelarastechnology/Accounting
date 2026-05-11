<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    public function test_rupiah_formats_correctly(): void
    {
        $this->assertEquals('Rp 1.000', rupiah(1000));
        $this->assertEquals('Rp 10.000', rupiah(10000));
        $this->assertEquals('Rp 100.000', rupiah(100000));
        $this->assertEquals('Rp 1.000.000', rupiah(1000000));
    }

    public function test_rupiah_without_prefix(): void
    {
        $this->assertEquals('1.000', rupiah(1000, false));
        $this->assertEquals('1.000.000', rupiah(1000000, false));
    }

    public function test_rupiah_with_zero(): void
    {
        $this->assertEquals('Rp 0', rupiah(0));
        $this->assertEquals('0', rupiah(0, false));
    }

    public function test_rupiah_with_string_input(): void
    {
        $this->assertEquals('Rp 1.000', rupiah('1000'));
        $this->assertEquals('Rp 1.500', rupiah('1500'));
    }

    public function test_rupiah_rounds_down_decimals(): void
    {
        $this->assertEquals('Rp 1.001', rupiah(1000.7));
    }
}
