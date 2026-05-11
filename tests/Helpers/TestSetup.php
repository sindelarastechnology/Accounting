<?php

namespace Tests\Helpers;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Period;
use App\Models\Product;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

trait TestSetup
{
    protected array $accountIds = [];
    protected ?Period $currentPeriod = null;

    protected function setupTestDatabase(): void
    {
        $this->seed(\Tests\TestDatabaseSeeder::class);
        $this->accountIds = Account::pluck('id', 'code')->toArray();
        $this->currentPeriod = Period::where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->where('is_closed', false)
            ->first();
    }

    protected function getAccountId(string $code): int
    {
        return $this->accountIds[$code] ?? Account::where('code', $code)->value('id');
    }

    protected function createContact(array $overrides = []): Contact
    {
        return Contact::create(array_merge([
            'code' => 'CUST-' . fake()->unique()->randomNumber(5),
            'name' => fake()->company(),
            'type' => 'customer',
            'email' => fake()->email(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'is_active' => true,
        ], $overrides));
    }

    protected function createSupplier(array $overrides = []): Contact
    {
        return $this->createContact(array_merge(['type' => 'supplier'], $overrides));
    }

    protected function createProduct(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'code' => 'PRD-' . fake()->unique()->randomNumber(5),
            'name' => fake()->word(),
            'type' => 'goods',
            'unit' => 'pcs',
            'purchase_price' => 50000,
            'selling_price' => 75000,
            'stock_on_hand' => 100,
            'revenue_account_id' => $this->getAccountId('4100-00-010'),
            'cogs_account_id' => $this->getAccountId('5100-00-010'),
            'inventory_account_id' => $this->getAccountId('1400-00-010'),
            'purchase_account_id' => $this->getAccountId('1400-00-010'),
            'is_active' => true,
        ], $overrides));
    }

    protected function createServiceProduct(array $overrides = []): Product
    {
        return $this->createProduct(array_merge([
            'type' => 'service',
            'revenue_account_id' => $this->getAccountId('4100-00-010'),
            'inventory_account_id' => null,
            'cogs_account_id' => null,
            'purchase_account_id' => null,
        ], $overrides));
    }

    protected function createWallet(array $overrides = []): Wallet
    {
        return Wallet::create(array_merge([
            'name' => 'Kas Kecil',
            'type' => 'cash',
            'account_id' => $this->getAccountId('1100-00-010'),
            'opening_balance' => 0,
            'is_active' => true,
        ], $overrides));
    }

    protected function createBankWallet(array $overrides = []): Wallet
    {
        return Wallet::create(array_merge([
            'name' => 'Bank BCA',
            'type' => 'bank',
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder' => 'Test',
            'account_id' => $this->getAccountId('1200-00-010'),
            'opening_balance' => 0,
            'is_active' => true,
        ], $overrides));
    }

    protected function today(): string
    {
        return Carbon::now()->format('Y-m-d');
    }

    protected function periodId(): int
    {
        return $this->currentPeriod->id;
    }
}
