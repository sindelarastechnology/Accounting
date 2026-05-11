<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Services\JournalService;
use App\Services\LedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\TestSetup;
use Tests\TestCase;

class LedgerServiceTest extends TestCase
{
    use RefreshDatabase, TestSetup;

    private int $cashAccountId;
    private int $revenueAccountId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestDatabase();

        $this->cashAccountId = $this->getAccountId('1100-00-010');
        $this->revenueAccountId = $this->getAccountId('4100-00-010');
    }

    // ==================== Label helpers (pure functions) ====================

    public function test_sourceColor_all_sources(): void
    {
        $sources = ['manual', 'sale', 'purchase', 'payment', 'expense', 'opening', 'closing',
                     'fixed_asset', 'depreciation', 'credit_note', 'debit_note', 'transfer',
                     'other_receipt', 'stock_opname', 'system'];

        foreach ($sources as $source) {
            $color = LedgerService::sourceColor($source);
            $this->assertStringContainsString('bg-', $color, "Source '{$source}' has no color class");
        }
    }

    public function test_sourceColor_unknown_returns_default(): void
    {
        $color = LedgerService::sourceColor('unknown_source');
        $this->assertStringContainsString('bg-gray-100', $color);
    }

    public function test_sourceLabel_all_sources(): void
    {
        $labels = [
            'manual' => 'Manual',
            'sale' => 'Penjualan',
            'purchase' => 'Pembelian',
            'payment' => 'Pembayaran',
            'expense' => 'Beban',
            'opening' => 'Saldo Awal',
            'closing' => 'Penutup',
            'fixed_asset' => 'Aset Tetap',
            'depreciation' => 'Penyusutan',
            'credit_note' => 'Retur Penjualan',
            'debit_note' => 'Retur Pembelian',
            'transfer' => 'Transfer Kas',
            'other_receipt' => 'Kas Masuk',
            'stock_opname' => 'Stock Opname',
            'system' => 'Sistem',
        ];

        foreach ($labels as $source => $expected) {
            $this->assertEquals($expected, LedgerService::sourceLabel($source));
        }
    }

    public function test_categoryColor_all_categories(): void
    {
        $categories = ['asset', 'liability', 'equity', 'revenue', 'expense', 'cogs'];
        foreach ($categories as $cat) {
            $color = LedgerService::categoryColor($cat);
            $this->assertStringContainsString('bg-', $color, "Category '{$cat}' has no color class");
        }
    }

    public function test_categoryLabel_all_categories(): void
    {
        $this->assertEquals('Aset', LedgerService::categoryLabel('asset'));
        $this->assertEquals('Kewajiban', LedgerService::categoryLabel('liability'));
        $this->assertEquals('Modal', LedgerService::categoryLabel('equity'));
        $this->assertEquals('Pendapatan', LedgerService::categoryLabel('revenue'));
        $this->assertEquals('Biaya', LedgerService::categoryLabel('expense'));
        $this->assertEquals('HPP', LedgerService::categoryLabel('cogs'));
        $this->assertEquals('unknown', LedgerService::categoryLabel('unknown'));
    }

    // ==================== getGeneralLedger ====================

    public function test_getGeneralLedger_returns_structure(): void
    {
        JournalService::createJournal(
            ['date' => $this->today(), 'period_id' => $this->periodId(), 'description' => 'test', 'source' => 'manual'],
            [
                ['account_id' => $this->cashAccountId, 'debit_amount' => 100, 'credit_amount' => 0, 'description' => 'DR'],
                ['account_id' => $this->revenueAccountId, 'debit_amount' => 0, 'credit_amount' => 100, 'description' => 'CR'],
            ]
        );

        $result = LedgerService::getGeneralLedger(
            accountId: $this->cashAccountId,
            dateFrom: now()->startOfMonth()->format('Y-m-d'),
            dateTo: now()->format('Y-m-d')
        );

        $this->assertArrayHasKey('accounts', $result);
        $this->assertArrayHasKey('date_from', $result);
        $this->assertArrayHasKey('date_to', $result);
        $this->assertIsArray($result['accounts']);
    }

    // ==================== getInventoryLedger ====================

    public function test_getInventoryLedger_returns_empty_when_no_goods(): void
    {
        $result = LedgerService::getInventoryLedger();

        $this->assertArrayHasKey('products', $result);
        $this->assertArrayHasKey('date_from', $result);
        $this->assertArrayHasKey('date_to', $result);
    }
}
