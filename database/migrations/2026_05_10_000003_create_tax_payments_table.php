<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained('periods');
            $table->foreignId('account_id')->constrained('accounts')
                  ->comment('Akun kas/bank untuk pembayaran');
            $table->foreignId('journal_id')->nullable()->constrained('journals')
                  ->nullOnDelete()->comment('Journal entry yang dibuat saat posting');
            $table->foreignId('created_by')->constrained('users');

            $table->string('document_number', 50)->unique();
            $table->string('tax_type', 20);
            $table->date('payment_date');
            $table->decimal('amount', 20, 4);
            $table->string('reference', 100)->nullable()
                  ->comment('Nomor SSP / NTPN');
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tax_type', 'status']);
            $table->index('payment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_payments');
    }
};
