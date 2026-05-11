<?php

namespace Tests\Feature;

use App\Helpers\AccountResolver;
use App\Models\Account;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Tests\Helpers\TestSetup;
use Tests\TestCase;

class AccountResolverTest extends TestCase
{
    use RefreshDatabase, TestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestDatabase();
    }

    public function test_resolve_from_setting(): void
    {
        $account = Account::factory()->create(['code' => '9999-00-010', 'is_header' => false, 'is_active' => true]);
        Setting::set('custom_account_id', $account->id, 'integer');

        $resolved = AccountResolver::resolve('custom_account_id', '9999-00-010');

        $this->assertEquals($account->id, $resolved);
    }

    public function test_resolve_from_fallback_code(): void
    {
        $resolved = AccountResolver::resolve('ar_account_id', '1300-00-020');

        $expected = $this->getAccountId('1300-00-020');
        $this->assertEquals($expected, $resolved);
    }

    public function test_resolve_throws_exception_when_not_found(): void
    {
        $this->expectException(RuntimeException::class);

        AccountResolver::resolve('nonexistent_key', '9999-99-999');
    }

    public function test_resolve_caches_result(): void
    {
        Cache::forget('account_resolver_ar_account_id');

        $result1 = AccountResolver::resolve('ar_account_id', '1300-00-020');
        $result2 = AccountResolver::resolve('ar_account_id', '1300-00-020');

        $this->assertEquals($result1, $result2);
    }

    public function test_receivable(): void
    {
        $this->assertEquals($this->getAccountId('1300-00-020'), AccountResolver::receivable());
    }

    public function test_payable(): void
    {
        $this->assertEquals($this->getAccountId('2100-00-020'), AccountResolver::payable());
    }

    public function test_revenue(): void
    {
        $this->assertEquals($this->getAccountId('4100-00-010'), AccountResolver::revenue());
    }

    public function test_inventory(): void
    {
        $this->assertEquals($this->getAccountId('1400-00-010'), AccountResolver::inventory());
    }

    public function test_cogs(): void
    {
        $this->assertEquals($this->getAccountId('5100-00-010'), AccountResolver::cogs());
    }

    public function test_taxPayable(): void
    {
        $this->assertEquals($this->getAccountId('2100-00-070'), AccountResolver::taxPayable());
    }

    public function test_taxReceivable(): void
    {
        $this->assertEquals($this->getAccountId('1500-00-030'), AccountResolver::taxReceivable());
    }

    public function test_pphPayable(): void
    {
        $this->assertEquals($this->getAccountId('2100-00-071'), AccountResolver::pphPayable());
    }

    public function test_pphPrepaid(): void
    {
        $this->assertEquals($this->getAccountId('1500-00-040'), AccountResolver::pphPrepaid());
    }

    public function test_expense(): void
    {
        $this->assertEquals($this->getAccountId('6110-00-010'), AccountResolver::expense());
    }

    public function test_clearCache_removes_all_keys(): void
    {
        AccountResolver::receivable();
        AccountResolver::payable();

        AccountResolver::clearCache();

        $this->assertFalse(Cache::has('account_resolver_ar_account_id'));
        $this->assertFalse(Cache::has('account_resolver_ap_account_id'));
    }
}
