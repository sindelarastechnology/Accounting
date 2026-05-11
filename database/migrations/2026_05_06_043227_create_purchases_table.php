<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TABEL: purchases (Faktur Pembelian / Purchase Order)
 *
 * Merekam pembelian dari supplier. Saat diposting, sistem membuat jurnal
 * double-entry pembelian.
 *
 * ALUR JURNAL PEMBELIAN (Accrual Basis):
 * Saat purchase diposting:
 *   Debit:  Persediaan / Akun Beban  (nilai pembelian sebelum pajak)
 *   Debit:  PPN Masukan              (jika PKP dan ada PPN)
 *   Kredit: Hutang Usaha             (total termasuk PPN exclusive)
 *
 * Saat pembayaran ke supplier:
 *   Debit:  Hutang Usaha             (melunasi hutang)
 *   Kredit: Kas / Bank               (jumlah dibayar)
 *   Kredit: PPh Dipotong             (jika ada withholding — mengurangi pembayaran)
 *
 * STATUS ALUR:
 *   draft → posted → partially_paid → paid
 *                 ↘ cancelled
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();                     // "PO-2024-0001"
            $table->foreignId('contact_id')->constrained('contacts');   // Supplier
            $table->foreignId('period_id')->constrained('periods');
            $table->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();

            $table->date('date');
            $table->date('due_date')->nullable();
            $table->string('supplier_invoice_number', 50)->nullable();  // No. faktur supplier

            // Nominal
            $table->decimal('subtotal', 18, 4)->default(0);
            $table->decimal('discount_amount', 18, 4)->default(0);
            $table->decimal('tax_amount', 18, 4)->default(0);
            $table->decimal('total', 18, 4)->default(0);
            $table->decimal('paid_amount', 18, 4)->default(0);
            $table->decimal('due_amount', 18, 4)->default(0);

            $table->enum('status', ['draft', 'posted', 'partially_paid', 'paid', 'cancelled'])
                  ->default('draft');

            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['contact_id', 'status']);
            $table->index(['period_id', 'date']);
        });

        /**
         * TABEL: purchase_items (Detail Item Pembelian)
         */
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('description', 255);

            $table->decimal('qty', 18, 4)->default(1);
            $table->string('unit', 30)->nullable();
            $table->decimal('unit_price', 18, 4)->default(0);
            $table->decimal('discount_percent', 8, 4)->default(0);
            $table->decimal('discount_amount', 18, 4)->default(0);
            $table->decimal('subtotal', 18, 4)->default(0);

            // Akun tujuan pembelian (beban atau persediaan)
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();

            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('purchase_id');
        });

        /**
         * TABEL: purchase_taxes (Detail Pajak Pembelian)
         */
        Schema::create('purchase_taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('tax_rule_id')->constrained('tax_rules');

            $table->string('tax_code', 30);
            $table->string('tax_name', 100);
            $table->enum('method', ['exclusive', 'inclusive', 'withholding']);
            $table->decimal('rate', 8, 4);
            $table->decimal('base_amount', 18, 4);
            $table->decimal('tax_amount', 18, 4);

            $table->foreignId('debit_account_id')->constrained('accounts');
            $table->foreignId('credit_account_id')->constrained('accounts');
            $table->timestamps();

            $table->index('purchase_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_taxes');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
    }
};