<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TABEL: contacts (Kontak: Customer & Supplier)
 *
 * Desain universal: satu tabel untuk semua pihak eksternal.
 * Satu kontak bisa berperan sebagai customer DAN supplier sekaligus.
 *
 * RELASI:
 * - ar_account_id → accounts.id (akun Piutang Usaha, default dari settings)
 * - ap_account_id → accounts.id (akun Hutang Usaha, default dari settings)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->nullable()->unique();   // Kode kontak opsional
            $table->string('name', 150);
            $table->enum('type', ['customer', 'supplier', 'both'])->default('customer');
            $table->string('email', 100)->nullable();
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 80)->nullable();
            $table->string('tax_number', 30)->nullable();       // NPWP
            $table->string('contact_person', 100)->nullable();  // PIC / nama kontak

            // Akun piutang & hutang spesifik kontak (override dari settings)
            $table->foreignId('ar_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('ap_account_id')->nullable()->constrained('accounts')->nullOnDelete();

            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};