<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fund_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('period_id')->constrained('periods');
            $table->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->date('date');
            $table->foreignId('from_wallet_id')->constrained('wallets');
            $table->foreignId('to_wallet_id')->constrained('wallets');
            $table->decimal('amount', 18, 4);
            $table->decimal('fee_amount', 18, 4)->default(0);
            $table->foreignId('fee_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'posted', 'cancelled'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['period_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fund_transfers');
    }
};
