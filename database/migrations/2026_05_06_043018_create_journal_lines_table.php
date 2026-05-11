<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TABEL: journal_lines (Baris Jurnal / Ledger Entry)
 *
 * Detail debit/kredit dari setiap jurnal.
 *
 * ATURAN DOUBLE-ENTRY (wajib dipaksa di level aplikasi + database trigger jika perlu):
 *   Untuk setiap journal_id:
 *     SUM(debit_amount) MUST EQUAL SUM(credit_amount)
 *
 * STRUKTUR PER BARIS:
 * - Setiap baris adalah SATU sisi (debit ATAU kredit), bukan keduanya.
 * - debit_amount > 0 dan credit_amount = 0, ATAU sebaliknya.
 * - Tidak boleh ada baris dengan keduanya > 0.
 *
 * CONTOH JURNAL PENJUALAN TUNAI Rp1.100.000 (termasuk PPN 11%):
 * ┌─────────────────────────┬──────────────┬──────────────┐
 * │ Akun                    │ Debit        │ Kredit       │
 * ├─────────────────────────┼──────────────┼──────────────┤
 * │ Kas (1100)              │ 1.100.000    │ -            │
 * │ Pendapatan Jasa (4100)  │ -            │ 1.000.000    │
 * │ PPN Keluaran (2201)     │ -            │   100.000    │
 * ├─────────────────────────┼──────────────┼──────────────┤
 * │ TOTAL                   │ 1.100.000    │ 1.100.000    │
 * └─────────────────────────┴──────────────┴──────────────┘
 *
 * wallet_id: opsional, hanya diisi jika akun adalah akun kas/bank
 *            memungkinkan rekonsiliasi per rekening bank
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained('journals')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts');
            $table->decimal('debit_amount', 18, 4)->default(0);
            $table->decimal('credit_amount', 18, 4)->default(0);

            // Opsional: rekening kas/bank yang terlibat (untuk rekonsiliasi)
            $table->foreignId('wallet_id')->nullable()->constrained('wallets')->nullOnDelete();

            $table->string('description', 255)->nullable();     // Keterangan baris
            $table->timestamps();

            $table->index('journal_id');
            $table->index('account_id');
            $table->index('wallet_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_lines');
    }
};