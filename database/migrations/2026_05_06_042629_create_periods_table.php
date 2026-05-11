<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TABEL: periods (Periode Akuntansi)
 * Fungsi: Membatasi rentang waktu transaksi keuangan.
 *         Periode yang sudah ditutup (is_closed=true) tidak bisa diubah.
 * Pola : Semua jurnal/transaksi wajib merujuk ke periode aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periods', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);                    // Contoh: "Januari 2024", "Q1 2024"
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_closed')->default(false);
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periods');
    }
};