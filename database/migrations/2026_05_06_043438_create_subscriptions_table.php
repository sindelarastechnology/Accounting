<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TABEL: subscriptions (Langganan Pelanggan)
 *
 * OPSIONAL - Aktifkan jika bisnis menggunakan model recurring billing
 * (internet, SaaS, sewa bulanan, maintenance contract, dll).
 *
 * Fungsi:
 * - Melacak paket langganan pelanggan aktif
 * - Dasar auto-generate invoice bulanan/berkala
 *
 * ALUR:
 * 1. Buat subscription untuk customer
 * 2. Sistem auto-generate invoice sesuai billing_cycle
 * 3. Invoice yang digenerate merujuk ke subscription ini
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained('contacts');   // Customer
            $table->foreignId('product_id')->constrained('products');   // Paket langganan

            $table->date('start_date');
            $table->date('end_date')->nullable();                       // null = tidak ada batas

            $table->decimal('price', 18, 4);                           // Harga per siklus
            $table->integer('billing_cycle')->default(1);              // Jarak penagihan
            $table->enum('billing_unit', ['day', 'month', 'year'])->default('month');

            $table->date('next_invoice_date')->nullable();              // Tanggal invoice berikutnya
            $table->boolean('auto_invoice')->default(true);            // Generate otomatis?

            $table->enum('status', ['active', 'inactive', 'cancelled'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['contact_id', 'status']);
            $table->index('next_invoice_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};