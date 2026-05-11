<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TABEL: inventory_movements (Mutasi Stok / Kartu Persediaan)
 *
 * Merekam setiap pergerakan stok barang (masuk, keluar, penyesuaian).
 * Ini adalah implementasi perpetual inventory system.
 *
 * SUMBER MUTASI (ref_type):
 * ┌────────────────┬──────────────────────────────────────────────────────┐
 * │ ref_type       │ Keterangan                                           │
 * ├────────────────┼──────────────────────────────────────────────────────┤
 * │ purchases      │ Barang masuk dari pembelian                          │
 * │ invoices       │ Barang keluar karena penjualan                       │
 * │ stock_opname   │ Penyesuaian stok dari stock opname                   │
 * │ manual         │ Penyesuaian manual                                   │
 * └────────────────┴──────────────────────────────────────────────────────┘
 *
 * JURNAL TERKAIT:
 * - Mutasi dari purchase → jurnal sudah dibuat di purchases
 * - Mutasi dari invoice (HPP) → jurnal HPP dibuat terpisah di invoice
 * - Mutasi dari stock opname → jurnal adjustment dibuat terpisah
 *
 * qty: positif = masuk, negatif = keluar
 * Saldo stok = SUM(qty) per product_id
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products');
            $table->date('date');
            $table->enum('type', ['in', 'out', 'adjustment']);      // Jenis mutasi
            $table->string('ref_type', 50)->nullable();             // Tabel asal
            $table->unsignedBigInteger('ref_id')->nullable();       // ID di tabel asal
            $table->decimal('qty', 18, 4);                          // + masuk / - keluar
            $table->decimal('unit_cost', 18, 4)->default(0);        // HPP per unit saat transaksi
            $table->decimal('total_cost', 18, 4)->default(0);       // qty × unit_cost
            $table->string('description', 255)->nullable();
            $table->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['product_id', 'date']);
            $table->index(['ref_type', 'ref_id']);
        });

        /**
         * TABEL: stock_opnames (Stock Opname)
         *
         * Header dari kegiatan stock opname (penghitungan fisik stok).
         */
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('period_id')->constrained('periods');
            $table->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->date('date');
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'posted', 'cancelled'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        /**
         * TABEL: stock_opname_items (Detail Stock Opname per Produk)
         */
        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained('stock_opnames')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('qty_book', 18, 4)->default(0);     // Stok menurut buku
            $table->decimal('qty_actual', 18, 4)->default(0);   // Stok fisik hasil hitung
            $table->decimal('qty_diff', 18, 4)->default(0);     // Selisih (actual - book)
            $table->decimal('unit_cost', 18, 4)->default(0);    // HPP per unit
            $table->decimal('total_diff_value', 18, 4)->default(0); // qty_diff × unit_cost
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('stock_opname_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opname_items');
        Schema::dropIfExists('stock_opnames');
        Schema::dropIfExists('inventory_movements');
    }
};