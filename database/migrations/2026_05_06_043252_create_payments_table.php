<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TABEL: payments (Pembayaran Invoice & PO)
 *
 * Merekam setiap penerimaan/pengeluaran kas untuk melunasi invoice/pembelian.
 *
 * ALUR JURNAL PEMBAYARAN INVOICE (penerimaan):
 *   Debit:  Kas/Bank (wallet_id)       = jumlah diterima bersih
 *   Debit:  PPh Dipotong (jika ada)    = potongan withholding
 *   Kredit: Piutang Usaha              = total amount (gross)
 *
 * ALUR JURNAL PEMBAYARAN PO (pengeluaran):
 *   Debit:  Hutang Usaha               = total amount (gross)
 *   Kredit: Kas/Bank (wallet_id)       = jumlah dibayar bersih
 *   Kredit: PPh Dipotong (jika ada)    = potongan withholding
 *
 * payable_type (polymorphic):
 * - "invoices"  → pembayaran dari customer (penerimaan)
 * - "purchases" → pembayaran ke supplier (pengeluaran)
 *
 * STATUS:
 *   pending → verified → (jurnal dibuat otomatis saat verified)
 *          ↘ cancelled
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();             // "PAY-2024-0001"

            // Polymorphic: bisa untuk invoice atau purchase
            $table->string('payable_type', 50);                // "invoices" atau "purchases"
            $table->unsignedBigInteger('payable_id');

            $table->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->foreignId('period_id')->constrained('periods');
            $table->foreignId('wallet_id')->constrained('wallets'); // Rekening kas/bank

            $table->date('date');
            $table->decimal('amount', 18, 4);                  // Jumlah bruto (sebelum potongan)
            $table->decimal('withholding_amount', 18, 4)->default(0); // Potongan withholding

            $table->enum('method', ['cash', 'transfer', 'ewallet', 'giro', 'other'])
                  ->default('cash');

            $table->string('reference', 100)->nullable();      // No. bukti / no. transfer
            $table->text('notes')->nullable();

            $table->enum('status', ['pending', 'verified', 'cancelled'])->default('pending');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['payable_type', 'payable_id']);
            $table->index(['period_id', 'date']);
            $table->index('wallet_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};