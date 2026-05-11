<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prepaid_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained('periods');
            $table->foreignId('asset_account_id')->constrained('accounts');
            $table->foreignId('expense_account_id')->constrained('accounts');
            $table->string('description');
            $table->decimal('total_amount', 18, 4);
            $table->decimal('remaining_amount', 18, 4);
            $table->date('start_date');
            $table->integer('total_months');
            $table->integer('months_amortized')->default(0);
            $table->decimal('monthly_amount', 18, 4);
            $table->string('status')->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('journal_id')->nullable()->constrained('journals');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index(['period_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prepaid_expenses');
    }
};
