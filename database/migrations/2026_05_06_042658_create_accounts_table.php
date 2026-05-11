<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TABEL: accounts (Bagan Akun / Chart of Accounts)
 *
 * Fondasi double-entry: Setiap transaksi harus menyeimbangkan Debit = Kredit
 * menggunakan akun dari tabel ini.
 *
 * KATEGORI & POSISI NORMAL:
 * ┌──────────────┬───────────────┬───────────────────────────────────┐
 * │ Kategori     │ Posisi Normal │ Keterangan                        │
 * ├──────────────┼───────────────┼───────────────────────────────────┤
 * │ asset        │ debit         │ Harta perusahaan                  │
 * │ liability    │ credit        │ Kewajiban / hutang                │
 * │ equity       │ credit        │ Modal pemilik                     │
 * │ revenue      │ credit        │ Pendapatan                        │
 * │ expense      │ debit         │ Beban / pengeluaran               │
 * │ cogs         │ debit         │ Harga pokok penjualan             │
 * └──────────────┴───────────────┴───────────────────────────────────┘
 *
 * STRUKTUR HIERARKI:
 * - parent_id NULL  = akun induk (header, tidak bisa dipakai di jurnal)
 * - parent_id IS SET = akun detail (bisa dipakai di jurnal)
 * - is_header = true  → akun induk, tidak boleh masuk journal_lines
 *
 * KONVENSI KODE AKUN:
 * 1xxx = Aset         (1100=Kas, 1200=Piutang, 1300=Persediaan, 1500=Aset Tetap)
 * 2xxx = Liabilitas   (2100=Hutang Usaha, 2200=Hutang Pajak)
 * 3xxx = Ekuitas      (3100=Modal, 3200=Laba Ditahan)
 * 4xxx = Pendapatan   (4100=Penjualan, 4200=Pendapatan Jasa)
 * 5xxx = HPP/COGS     (5100=HPP Barang, 5200=HPP Jasa)
 * 6xxx = Beban Operasi(6100=Beban Gaji, 6200=Beban Sewa)
 * 7xxx = Pendapatan Lain-lain
 * 8xxx = Beban Lain-lain
 * 9xxx = Akun Penutup (digunakan saat closing periode)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();           // Kode akun, misal: "1101"
            $table->string('name', 150);                    // Nama akun, misal: "Kas Tunai"
            $table->enum('category', [
                'asset',       // Aset
                'liability',   // Liabilitas
                'equity',      // Ekuitas
                'revenue',     // Pendapatan
                'cogs',        // Harga Pokok Penjualan
                'expense',     // Beban
            ]);
            $table->enum('normal_balance', ['debit', 'credit']); // Posisi normal saldo
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->boolean('is_header')->default(false);   // true = tidak bisa dipakai di jurnal
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['category', 'is_active']);
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};