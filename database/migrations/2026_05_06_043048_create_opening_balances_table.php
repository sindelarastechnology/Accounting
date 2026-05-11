<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TABEL: opening_balances (Saldo Awal Akun)
 *
 * Fungsi: Mencatat saldo awal tiap akun saat sistem pertama kali digunakan
 *         atau saat periode baru dibuka (hasil closing periode sebelumnya).
 *
 * ALUR SALDO AWAL:
 * 1. Input pertama kali → user input saldo per akun → disimpan ke tabel ini
 * 2. Saldo awal juga dijurnal sebagai jurnal "opening" (type=opening) agar
 *    masuk ke buku besar dan neraca saldo terhitung otomatis.
 *
 * PENTING:
 * - Untuk akun Kas/Bank, wallet_id wajib diisi agar saldo per rekening tercatat.
 * - amount bisa negatif untuk akun yang memiliki saldo berlawanan posisi normal.
 *   Namun best practice: gunakan nilai absolut + tandai dengan is_debit flag.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opening_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained('periods');
            $table->foreignId('account_id')->constrained('accounts');
            $table->foreignId('wallet_id')->nullable()->constrained('wallets')->nullOnDelete();
            $table->decimal('amount', 18, 4)->default(0);       // Nilai saldo awal (absolut)
            $table->enum('position', ['debit', 'credit']);      // Posisi saldo (debit/kredit)
            $table->timestamps();

            $table->unique(['period_id', 'account_id', 'wallet_id'], 'ob_period_account_wallet_unique');
            $table->index('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opening_balances');
    }
};