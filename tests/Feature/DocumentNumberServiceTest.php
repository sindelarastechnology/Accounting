<?php

namespace Tests\Feature;

use App\Services\DocumentNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestSetup;
use Tests\TestCase;

class DocumentNumberServiceTest extends TestCase
{
    use RefreshDatabase, TestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestDatabase();
    }

    public function test_generates_first_number_for_type(): void
    {
        $number = DocumentNumberService::generate('invoices', 'INV', '2025-01-15');

        $this->assertStringStartsWith('INV/2025/01/', $number);
        $this->assertStringEndsWith('00001', $number);

        $counter = DB::table('document_counters')
            ->where('type', 'invoices')->where('year', 2025)->where('month', 1)
            ->first();
        $this->assertNotNull($counter);
        $this->assertEquals(1, $counter->last_number);
    }

    public function test_increments_number_sequentially(): void
    {
        $num1 = DocumentNumberService::generate('invoices', 'INV', '2025-01-15');
        $num2 = DocumentNumberService::generate('invoices', 'INV', '2025-01-15');

        $this->assertStringEndsWith('00001', $num1);
        $this->assertStringEndsWith('00002', $num2);
    }

    public function test_different_types_have_separate_counters(): void
    {
        $invNumber = DocumentNumberService::generate('invoices', 'INV', '2025-01-15');
        $poNumber = DocumentNumberService::generate('purchases', 'PO', '2025-01-15');

        $this->assertStringEndsWith('00001', $invNumber);
        $this->assertStringEndsWith('00001', $poNumber);
    }

    public function test_different_months_have_separate_counters(): void
    {
        $janNumber = DocumentNumberService::generate('invoices', 'INV', '2025-01-15');
        $febNumber = DocumentNumberService::generate('invoices', 'INV', '2025-02-15');

        $this->assertStringEndsWith('00001', $janNumber);
        $this->assertStringEndsWith('00001', $febNumber);
    }

    public function test_different_years_have_separate_counters(): void
    {
        $num2025 = DocumentNumberService::generate('invoices', 'INV', '2025-01-15');
        $num2026 = DocumentNumberService::generate('invoices', 'INV', '2026-01-15');

        $this->assertStringContainsString('/2025/', $num2025);
        $this->assertStringContainsString('/2026/', $num2026);
        $this->assertStringEndsWith('00001', $num2025);
        $this->assertStringEndsWith('00001', $num2026);
    }

    public function test_generates_valid_format(): void
    {
        $number = DocumentNumberService::generate('journals', 'JNL', '2025-12-31');

        $this->assertMatchesRegularExpression('/^JNL\/\d{4}\/\d{2}\/\d{5}$/', $number);
    }

    public function test_handles_race_condition_with_lock(): void
    {
        DB::beginTransaction();
        $num1 = DocumentNumberService::generate('invoices', 'INV', '2025-01-15');
        DB::rollBack();

        $num2 = DocumentNumberService::generate('invoices', 'INV', '2025-01-15');
        $this->assertStringEndsWith('00001', $num2);
    }
}
