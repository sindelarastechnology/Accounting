<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TABEL: wallets (Kas & Rekening Bank)
 *
 * Fungsi: Merepresentasikan rekening fisik (kas, bank, e-wallet).
 *         Setiap wallet terhubung ke 1 akun COA (kategori asset/kas).
 *
 * Relasi ke accounts:
 * - account_id → accounts.id (harus akun bertipe asset, posisi normal debit)
 *
 * CATATAN:
 * - opening_balance = saldo awal saat periode pertama dibuka, dicatat di opening_balances
 * - Saldo aktual dihitung dari journal_lines (tidak disimpan di sini, agar tidak redundan)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);                        // "Kas Besar", "BCA 1234"
            $table->enum('type', ['cash', 'bank', 'ewallet']); // Jenis rekening
            $table->string('bank_name', 100)->nullable();       // Nama bank / e-wallet
            $table->string('account_number', 50)->nullable();   // Nomor rekening
            $table->string('account_holder', 100)->nullable();  // Nama pemilik rekening
            $table->foreignId('account_id')->constrained('accounts'); // Akun COA terkait
            $table->decimal('opening_balance', 18, 4)->default(0); // Saldo awal
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};