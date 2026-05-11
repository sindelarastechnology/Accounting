<?php

namespace Tests\Feature;

use App\Exceptions\InvalidAccountException;
use App\Models\Account;
use App\Services\FundTransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\TestSetup;
use Tests\TestCase;

class FundTransferServiceTest extends TestCase
{
    use RefreshDatabase, TestSetup;

    private $fromWallet;
    private $toWallet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestDatabase();

        $this->fromWallet = $this->createWallet();
        $this->toWallet = $this->createBankWallet();
    }

    public function test_createTransfer_creates_draft(): void
    {
        $transfer = FundTransferService::createTransfer([
            'from_wallet_id' => $this->fromWallet->id,
            'to_wallet_id' => $this->toWallet->id,
            'date' => $this->today(),
            'amount' => 500000,
        ]);

        $this->assertEquals('draft', $transfer->status);
        $this->assertEquals(500000, (float) $transfer->amount);
    }

    public function test_createTransfer_same_wallet_throws(): void
    {
        $this->expectException(InvalidAccountException::class);
        FundTransferService::createTransfer([
            'from_wallet_id' => $this->fromWallet->id,
            'to_wallet_id' => $this->fromWallet->id,
            'date' => $this->today(),
            'amount' => 500000,
        ]);
    }

    public function test_createTransfer_zero_amount_throws(): void
    {
        $this->expectException(InvalidAccountException::class);
        FundTransferService::createTransfer([
            'from_wallet_id' => $this->fromWallet->id,
            'to_wallet_id' => $this->toWallet->id,
            'date' => $this->today(),
            'amount' => 0,
        ]);
    }

    public function test_postTransfer_creates_journal(): void
    {
        $transfer = FundTransferService::createTransfer([
            'from_wallet_id' => $this->fromWallet->id,
            'to_wallet_id' => $this->toWallet->id,
            'date' => $this->today(),
            'amount' => 500000,
        ]);

        $result = FundTransferService::postTransfer($transfer);

        $this->assertEquals('posted', $result->status);
    }

    public function test_postTransfer_with_fee(): void
    {
        $feeAccount = Account::find($this->getAccountId('6110-00-010'));

        $transfer = FundTransferService::createTransfer([
            'from_wallet_id' => $this->fromWallet->id,
            'to_wallet_id' => $this->toWallet->id,
            'date' => $this->today(),
            'amount' => 500000,
            'fee_amount' => 5000,
            'fee_account_id' => $feeAccount->id,
        ]);

        $result = FundTransferService::postTransfer($transfer);

        $this->assertEquals('posted', $result->status);
    }

    public function test_cancelTransfer(): void
    {
        $transfer = FundTransferService::createTransfer([
            'from_wallet_id' => $this->fromWallet->id,
            'to_wallet_id' => $this->toWallet->id,
            'date' => $this->today(),
            'amount' => 500000,
        ]);
        FundTransferService::postTransfer($transfer);
        FundTransferService::cancelTransfer($transfer);

        $transfer->refresh();
        $this->assertEquals('cancelled', $transfer->status);
    }
}
