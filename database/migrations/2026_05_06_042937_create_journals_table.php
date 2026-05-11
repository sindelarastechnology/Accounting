<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TABEL: journals (Jurnal Umum)
 *
 * INI ADALAH INTI SISTEM DOUBLE-ENTRY BOOKKEEPING.
 *
 * Setiap transaksi keuangan menghasilkan 1 jurnal (header) + minimal 2 journal_lines
 * dengan syarat MUTLAK: SUM(debit) = SUM(kredit) per jurnal.
 *
 * SUMBER JURNAL (source):
 * ┌──────────────┬──────────────────────────────────────────────────────────┐
 * │ Source       │ Dibuat oleh                                              │
 * ├──────────────┼──────────────────────────────────────────────────────────┤
 * │ manual       │ Input jurnal manual oleh user                            │
 * │ sale         │ Otomatis dari Invoice (penjualan)                        │
 * │ purchase     │ Otomatis dari Purchase Order                             │
 * │ payment      │ Otomatis dari pembayaran invoice/PO                      │
 * │ expense      │ Otomatis dari pencatatan beban langsung                  │
 * │ stock_opname │ Otomatis dari stock opname (penyesuaian stok)            │
 * │ opening      │ Jurnal saldo awal periode                                │
 * │ closing      │ Jurnal penutup akhir periode                             │
 * └──────────────┴──────────────────────────────────────────────────────────┘
 *
 * TIPE JURNAL:
 * ┌──────────┬────────────────────────────────────────────────────────────┐
 * │ Type     │ Keterangan                                                 │
 * ├──────────┼────────────────────────────────────────────────────────────┤
 * │ normal   │ Transaksi biasa                                            │
 * │ reversal │ Jurnal pembalik (membalik jurnal sebelumnya)               │
 * │ void     │ Jurnal yang dibatalkan (dibuat void entry-nya)             │
 * │ closing  │ Jurnal penutup akhir periode                               │
 * │ opening  │ Jurnal saldo awal periode baru                             │
 * └──────────┴────────────────────────────────────────────────────────────┘
 *
 * ref_type + ref_id: traceability ke sumber transaksi
 * - ref_type = "invoices", ref_id = 42 → Invoice #42
 * - ref_type = "purchases", ref_id = 7 → PO #7
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();             // No jurnal: "JNL-2024-0001"
            $table->date('date');
            $table->foreignId('period_id')->constrained('periods');
            $table->string('description', 255);
            $table->enum('source', [
                'manual', 'sale', 'purchase', 'payment',
                'expense', 'stock_opname', 'opening', 'closing'
            ])->default('manual');
            $table->enum('type', ['normal', 'reversal', 'void', 'closing', 'opening'])->default('normal');

            // Traceability ke transaksi sumber
            $table->string('ref_type', 50)->nullable();         // Nama tabel sumber
            $table->unsignedBigInteger('ref_id')->nullable();   // ID di tabel sumber

            // Jurnal pembalik merujuk ke jurnal asal
            $table->foreignId('reversed_journal_id')->nullable()->constrained('journals')->nullOnDelete();

            $table->boolean('is_posted')->default(true);        // false = draft
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['date', 'period_id']);
            $table->index(['ref_type', 'ref_id']);
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journals');
    }
};