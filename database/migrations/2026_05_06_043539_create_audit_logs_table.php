<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TABEL: audit_logs (Jejak Audit)
 *
 * Mencatat semua perubahan data sensitif akuntansi.
 * Penting untuk kepatuhan dan keamanan data keuangan.
 *
 * Direkomendasikan menggunakan package spatie/laravel-activitylog,
 * namun tabel ini bisa digunakan jika ingin implementasi mandiri.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 30);                    // created, updated, deleted, posted, void
            $table->string('auditable_type', 100);          // Nama model/tabel
            $table->unsignedBigInteger('auditable_id');
            $table->json('old_values')->nullable();         // Data sebelum perubahan
            $table->json('new_values')->nullable();         // Data setelah perubahan
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at');

            $table->index(['auditable_type', 'auditable_id']);
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};