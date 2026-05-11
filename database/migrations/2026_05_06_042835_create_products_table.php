<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TABEL: products (Master Produk - Universal)
 *
 * Desain universal: bisa menampung BARANG, JASA, LANGGANAN, atau apapun.
 *
 * TYPE:
 * ┌──────────────┬────────────────────────────────────────────────────────────┐
 * │ Type         │ Keterangan                                                 │
 * ├──────────────┼────────────────────────────────────────────────────────────┤
 * │ goods        │ Barang fisik → ada pencatatan stok di inventory_movements  │
 * │ service      │ Jasa → tanpa stok, langsung ke akun pendapatan             │
 * │ subscription │ Langganan berkala (misal: paket internet, SaaS, dll)       │
 * │ bundle       │ Paket bundling beberapa produk                             │
 * └──────────────┴────────────────────────────────────────────────────────────┘
 *
 * RELASI AKUN:
 * - revenue_account_id    → akun pendapatan penjualan (override settings)
 * - cogs_account_id       → akun HPP (wajib jika type=goods)
 * - inventory_account_id  → akun persediaan (wajib jika type=goods)
 * - purchase_account_id   → akun beban/pembelian (untuk pembelian jasa/goods non-stok)
 *
 * CATATAN STOK:
 * - Stok aktual dihitung dari inventory_movements (method periodik/perpetual)
 * - stock_on_hand di sini hanya cache, tidak menjadi acuan akuntansi
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 200);
            $table->enum('type', ['goods', 'service', 'subscription', 'bundle'])->default('goods');
            $table->string('unit', 30)->nullable();              // Satuan: pcs, kg, meter, bulan, dll
            $table->text('description')->nullable();

            // Harga
            $table->decimal('purchase_price', 18, 4)->default(0);   // Harga beli / HPP default
            $table->decimal('selling_price', 18, 4)->default(0);    // Harga jual default

            // Stok (hanya relevan jika type = goods)
            $table->decimal('stock_on_hand', 18, 4)->default(0);    // Cache stok (tidak dijurnal)
            $table->decimal('stock_minimum', 18, 4)->default(0);    // Stok minimum (alert)

            // Pajak default
            $table->decimal('tax_rate', 8, 4)->default(0);          // Persentase pajak item

            // Relasi akun COA
            $table->foreignId('revenue_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('cogs_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('inventory_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('purchase_account_id')->nullable()->constrained('accounts')->nullOnDelete();

            // Untuk type=subscription
            $table->integer('subscription_duration')->nullable();    // Durasi (angka)
            $table->enum('subscription_unit', ['day', 'month', 'year'])->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};