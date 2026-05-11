<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('other_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('period_id')->constrained('periods');
            $table->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->date('date');
            $table->foreignId('wallet_id')->constrained('wallets');
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->enum('receipt_type', [
                'capital_injection', 'owner_loan', 'other_income', 'refund', 'other'
            ]);
            $table->decimal('amount', 18, 4);
            $table->foreignId('credit_account_id')->constrained('accounts');
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
        Schema::dropIfExists('other_receipts');
    }
};
