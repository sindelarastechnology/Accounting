<?php

namespace Tests\Feature;

use App\Exceptions\InvalidAccountException;
use App\Models\Account;
use App\Models\User;
use App\Services\TaxPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Helpers\TestSetup;
use Tests\TestCase;

class TaxPaymentServiceTest extends TestCase
{
    use RefreshDatabase, TestSetup;

    private $cashAccount;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestDatabase();

        $user = User::factory()->create();
        Auth::login($user);

        $this->cashAccount = Account::find($this->getAccountId('1100-00-010'));
    }

    public function test_create_creates_draft(): void
    {
        $taxPayment = TaxPaymentService::create([
            'period_id' => $this->periodId(),
            'account_id' => $this->cashAccount->id,
            'tax_type' => 'ppn',
            'payment_date' => $this->today(),
            'amount' => 550000,
        ]);

        $this->assertEquals('draft', $taxPayment->status);
        $this->assertEquals(550000, (float) $taxPayment->amount);
    }

    public function test_post_creates_journal(): void
    {
        $taxPayment = TaxPaymentService::create([
            'period_id' => $this->periodId(),
            'account_id' => $this->cashAccount->id,
            'tax_type' => 'ppn',
            'payment_date' => $this->today(),
            'amount' => 550000,
        ]);

        $result = TaxPaymentService::post($taxPayment);

        $this->assertEquals('posted', $result->status);
        $this->assertNotNull($result->journal_id);
    }

    public function test_void_tax_payment(): void
    {
        $taxPayment = TaxPaymentService::create([
            'period_id' => $this->periodId(),
            'account_id' => $this->cashAccount->id,
            'tax_type' => 'ppn',
            'payment_date' => $this->today(),
            'amount' => 550000,
        ]);
        TaxPaymentService::post($taxPayment);
        TaxPaymentService::void($taxPayment);

        $taxPayment->refresh();
        $this->assertEquals('void', $taxPayment->status);
    }

    public function test_create_invalid_tax_type_throws(): void
    {
        $taxPayment = TaxPaymentService::create([
            'period_id' => $this->periodId(),
            'account_id' => $this->cashAccount->id,
            'tax_type' => 'invalid',
            'payment_date' => $this->today(),
            'amount' => 100000,
        ]);

        $this->expectException(InvalidAccountException::class);
        TaxPaymentService::post($taxPayment);
    }
}
