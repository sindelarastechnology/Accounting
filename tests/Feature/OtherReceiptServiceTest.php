<?php

namespace Tests\Feature;

use App\Exceptions\InvalidAccountException;
use App\Models\Account;
use App\Services\OtherReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\TestSetup;
use Tests\TestCase;

class OtherReceiptServiceTest extends TestCase
{
    use RefreshDatabase, TestSetup;

    private $wallet;
    private $creditAccount;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestDatabase();

        $this->wallet = $this->createWallet();
        $this->creditAccount = Account::find($this->getAccountId('3100-00-020'));
    }

    public function test_createReceipt_creates_draft(): void
    {
        $receipt = OtherReceiptService::createReceipt([
            'period_id' => $this->periodId(),
            'date' => $this->today(),
            'wallet_id' => $this->wallet->id,
            'receipt_type' => 'capital_injection',
            'amount' => 10000000,
            'credit_account_id' => $this->creditAccount->id,
        ]);

        $this->assertEquals('draft', $receipt->status);
        $this->assertEquals(10000000, (float) $receipt->amount);
    }

    public function test_createReceipt_zero_amount_throws(): void
    {
        $this->expectException(InvalidAccountException::class);
        OtherReceiptService::createReceipt([
            'period_id' => $this->periodId(),
            'date' => $this->today(),
            'wallet_id' => $this->wallet->id,
            'receipt_type' => 'other_income',
            'amount' => 0,
            'credit_account_id' => $this->creditAccount->id,
        ]);
    }

    public function test_postReceipt_creates_journal(): void
    {
        $receipt = OtherReceiptService::createReceipt([
            'period_id' => $this->periodId(),
            'date' => $this->today(),
            'wallet_id' => $this->wallet->id,
            'receipt_type' => 'capital_injection',
            'amount' => 10000000,
            'credit_account_id' => $this->creditAccount->id,
        ]);

        $result = OtherReceiptService::postReceipt($receipt);

        $this->assertEquals('posted', $result->status);
        $this->assertNotNull($result->journal_id);
    }

    public function test_cancelReceipt(): void
    {
        $receipt = OtherReceiptService::createReceipt([
            'period_id' => $this->periodId(),
            'date' => $this->today(),
            'wallet_id' => $this->wallet->id,
            'receipt_type' => 'other_income',
            'amount' => 5000000,
            'credit_account_id' => $this->creditAccount->id,
        ]);
        OtherReceiptService::postReceipt($receipt);
        OtherReceiptService::cancelReceipt($receipt);

        $receipt->refresh();
        $this->assertEquals('cancelled', $receipt->status);
    }
}
