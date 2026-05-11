<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TABEL: invoice_items (Detail Item Invoice)
 *
 * Setiap baris item pada invoice. Bisa merujuk ke product master
 * atau diisi manual (description-based).
 *
 * KOLOM AKUN OVERRIDE:
 * - revenue_account_id: override akun pendapatan dari product/settings
 * - cogs_account_id   : override akun HPP (untuk goods dengan perpetual inventory)
 *
 * PERHITUNGAN:
 *   subtotal = qty × unit_price - discount_amount
 *   (pajak dihitung di invoice_taxes, bukan di sini)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('description', 255);                     // Bisa diisi manual

            $table->decimal('qty', 18, 4)->default(1);
            $table->string('unit', 30)->nullable();
            $table->decimal('unit_price', 18, 4)->default(0);       // Harga satuan
            $table->decimal('discount_percent', 8, 4)->default(0);  // Diskon %
            $table->decimal('discount_amount', 18, 4)->default(0);  // Diskon nominal
            $table->decimal('subtotal', 18, 4)->default(0);         // qty × price - diskon

            // Override akun dari product default
            $table->foreignId('revenue_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('cogs_account_id')->nullable()->constrained('accounts')->nullOnDelete();

            // HPP per unit (untuk barang dengan perpetual inventory)
            $table->decimal('cost_price', 18, 4)->default(0);

            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('invoice_id');
            $table->index('product_id');
        });

        /**
         * TABEL: invoice_taxes (Detail Pajak Invoice)
         *
         * Setiap pajak yang berlaku pada invoice (PPN, PPh, dll).
         * Dipisah dari invoice_items agar lebih fleksibel.
         *
         * Satu invoice bisa memiliki beberapa pajak:
         * - PPN 11% exclusive → menambah total invoice
         * - PPh23 2% withholding → mengurangi penerimaan (dipotong customer)
         *
         * RELASI JURNAL:
         * Saat posting invoice, jurnal menggunakan debit_account & credit_account
         * dari tax_rule untuk mencatat pajak.
         */
        Schema::create('invoice_taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('tax_rule_id')->constrained('tax_rules');

            // Snapshot data pajak saat transaksi (agar tidak berubah jika tax_rule diubah)
            $table->string('tax_code', 30);
            $table->string('tax_name', 100);
            $table->enum('method', ['exclusive', 'inclusive', 'withholding']);
            $table->decimal('rate', 8, 4);
            $table->decimal('base_amount', 18, 4);      // Dasar pengenaan pajak
            $table->decimal('tax_amount', 18, 4);       // Nilai pajak

            $table->foreignId('debit_account_id')->constrained('accounts');
            $table->foreignId('credit_account_id')->constrained('accounts');
            $table->timestamps();

            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_taxes');
        Schema::dropIfExists('invoice_items');
    }
};