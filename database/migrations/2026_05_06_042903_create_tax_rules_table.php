<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TABEL: tax_rules (Aturan Pajak)
 *
 * Sistem pajak yang fleksibel — mendukung berbagai jenis pajak Indonesia.
 *
 * JENIS PAJAK:
 * ┌──────────┬───────────────────────────────────────────────────────────────┐
 * │ Jenis    │ Keterangan                                                    │
 * ├──────────┼───────────────────────────────────────────────────────────────┤
 * │ ppn      │ PPN (Pajak Pertambahan Nilai) — biasanya 11%                  │
 * │ pph      │ PPh (Pajak Penghasilan) — withholding, misal PPh23 2%         │
 * │ other    │ Pajak/pungutan lain (misal: BHP USO, retribusi)               │
 * └──────────┴───────────────────────────────────────────────────────────────┘
 *
 * METODE:
 * ┌─────────────┬────────────────────────────────────────────────────────────┐
 * │ Metode      │ Keterangan                                                 │
 * ├─────────────┼────────────────────────────────────────────────────────────┤
 * │ exclusive   │ Pajak DITAMBAHKAN ke harga (tax = base × rate)             │
 * │ inclusive   │ Pajak SUDAH TERMASUK dalam harga                           │
 * │ withholding │ Pajak DIPOTONG dari nilai transaksi (misal PPh23)          │
 * └─────────────┴────────────────────────────────────────────────────────────┘
 *
 * RELASI AKUN:
 * - debit_account_id  → akun yang di-debit saat pajak dijurnal
 * - credit_account_id → akun yang di-kredit saat pajak dijurnal
 *
 * Contoh PPN Keluaran (penjualan, exclusive):
 *   Debit: Piutang (atau Kas)      Credit: PPN Keluaran (2200)
 *
 * Contoh PPh23 (withholding, pembelian jasa):
 *   Debit: Hutang PPh23 (2300)     Credit: -  (mengurangi pembayaran)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rules', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();               // "PPN_11", "PPH23_2"
            $table->string('name', 100);                        // "PPN 11%", "PPh Pasal 23 - 2%"
            $table->enum('type', ['ppn', 'pph', 'other']);
            $table->enum('module', ['sale', 'purchase', 'both'])->default('both');
            $table->enum('method', ['exclusive', 'inclusive', 'withholding']);
            $table->decimal('rate', 8, 4);                      // Persentase, misal: 11.0000
            $table->foreignId('debit_account_id')->constrained('accounts');
            $table->foreignId('credit_account_id')->constrained('accounts');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rules');
    }
};