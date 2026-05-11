<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TABEL: expenses (Beban / Pengeluaran Langsung)
 *
 * Untuk mencatat beban/pengeluaran yang langsung dibayar tunai/transfer,
 * TANPA melalui alur hutang (purchase → payment).
 *
 * Cocok untuk: biaya operasional harian, tagihan utilitas, biaya administrasi, dll.
 *
 * ALUR JURNAL EXPENSE:
 *   Debit:  Akun Beban (account_id)    = jumlah beban
 *   Kredit: Kas/Bank (wallet_id)       = jumlah dibayar
 *
 * Jika ada pajak (PPh):
 *   Debit:  Akun Beban                 = jumlah bruto
 *   Kredit: Hutang PPh Dipotong        = nilai PPh
 *   Kredit: Kas/Bank                   = jumlah neto
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();         // "EXP-2024-0001"
            $table->foreignId('period_id')->constrained('periods');
            $table->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();

            $table->date('date');
            $table->string('name', 150);                    // Nama/keterangan beban
            $table->foreignId('account_id')->constrained('accounts'); // Akun beban
            $table->foreignId('wallet_id')->constrained('wallets');   // Rekening pembayaran
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();

            $table->decimal('amount', 18, 4);               // Jumlah beban
            $table->string('receipt_number', 50)->nullable(); // No. bukti/nota

            $table->enum('status', ['draft', 'posted', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['period_id', 'date']);
            $table->index('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};