<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TABEL: invoices (Faktur Penjualan)
 *
 * Merekam tagihan ke customer. Saat invoice diposting, sistem otomatis
 * membuat jurnal double-entry penjualan.
 *
 * ALUR JURNAL INVOICE (Accrual Basis):
 * Saat invoice diposting:
 *   Debit:  Piutang Usaha     (sesuai total invoice)
 *   Kredit: Pendapatan        (subtotal sebelum pajak)
 *   Kredit: PPN Keluaran      (jika ada PPN exclusive)
 *
 * Saat pembayaran diterima (lihat: payments):
 *   Debit:  Kas / Bank        (jumlah diterima)
 *   Debit:  PPh Dipotong      (jika ada withholding)
 *   Kredit: Piutang Usaha     (melunasi piutang)
 *
 * STATUS ALUR:
 *   draft → posted → partially_paid → paid
 *                 ↘ cancelled (jika dibatalkan sebelum ada pembayaran)
 *
 * PERHITUNGAN:
 *   subtotal     = SUM(invoice_items.subtotal)
 *   tax_amount   = SUM(invoice_taxes.tax_amount) untuk pajak exclusive
 *   discount     = diskon keseluruhan invoice (jika ada)
 *   total        = subtotal + tax_amount (exclusive) - discount
 *   paid_amount  = SUM(payments.amount) — termasuk potongan withholding
 *   due_amount   = total - paid_amount
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();                     // "INV-2024-0001"
            $table->foreignId('contact_id')->constrained('contacts');   // Customer
            $table->foreignId('period_id')->constrained('periods');
            $table->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();

            $table->date('date');                           // Tanggal invoice
            $table->date('due_date')->nullable();           // Jatuh tempo

            // Nominal
            $table->decimal('subtotal', 18, 4)->default(0);    // Sebelum pajak & diskon
            $table->decimal('discount_amount', 18, 4)->default(0); // Diskon total invoice
            $table->decimal('tax_amount', 18, 4)->default(0);  // Total pajak exclusive
            $table->decimal('total', 18, 4)->default(0);       // Subtotal - diskon + pajak exclusive
            $table->decimal('paid_amount', 18, 4)->default(0); // Total sudah dibayar + dipotong
            $table->decimal('due_amount', 18, 4)->default(0);  // Sisa piutang

            $table->enum('status', ['draft', 'posted', 'partially_paid', 'paid', 'cancelled'])
                  ->default('draft');

            $table->text('notes')->nullable();
            $table->string('ref_number', 50)->nullable();      // No. referensi eksternal
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['contact_id', 'status']);
            $table->index(['period_id', 'date']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};