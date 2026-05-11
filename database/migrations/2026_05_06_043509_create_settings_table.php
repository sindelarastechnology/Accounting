<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TABEL: settings (Pengaturan Sistem)
 *
 * Konfigurasi akuntansi yang digunakan sebagai default seluruh sistem.
 * Disimpan sebagai key-value agar fleksibel ditambah tanpa migrasi baru.
 *
 * KEY AKUNTANSI PENTING:
 * ┌────────────────────────────┬────────────────────────────────────────────┐
 * │ Key                        │ Keterangan                                 │
 * ├────────────────────────────┼────────────────────────────────────────────┤
 * │ ar_account_id              │ Akun Piutang Usaha default                 │
 * │ ap_account_id              │ Akun Hutang Usaha default                  │
 * │ revenue_account_id         │ Akun Pendapatan default                    │
 * │ cogs_account_id            │ Akun HPP default                           │
 * │ inventory_account_id       │ Akun Persediaan default                    │
 * │ equity_account_id          │ Akun Modal default                         │
 * │ retained_earnings_id       │ Akun Laba Ditahan                          │
 * │ income_summary_id          │ Akun Ikhtisar Laba Rugi (closing)          │
 * │ ppn_output_account_id      │ Akun PPN Keluaran                          │
 * │ ppn_input_account_id       │ Akun PPN Masukan                           │
 * │ pph_payable_account_id     │ Akun Hutang PPh                            │
 * │ stock_gain_account_id      │ Akun Pendapatan Selisih Stok (opname +)    │
 * │ stock_loss_account_id      │ Akun Beban Selisih Stok (opname -)         │
 * │ default_tax_rate           │ Tarif pajak default (%)                    │
 * │ invoice_prefix             │ Prefix no. invoice: "INV"                  │
 * │ purchase_prefix            │ Prefix no. PO: "PO"                        │
 * │ journal_prefix             │ Prefix no. jurnal: "JNL"                   │
 * │ payment_prefix             │ Prefix no. pembayaran: "PAY"               │
 * │ expense_prefix             │ Prefix no. beban: "EXP"                    │
 * │ company_name               │ Nama perusahaan                            │
 * │ company_address            │ Alamat                                     │
 * │ company_phone              │ Telepon                                    │
 * │ company_tax_number         │ NPWP                                       │
 * │ company_logo               │ Path logo                                  │
 * │ currency_code              │ Mata uang: "IDR"                           │
 * │ currency_symbol            │ Simbol: "Rp"                               │
 * │ decimal_separator          │ Pemisah desimal: "," atau "."              │
 * │ thousand_separator         │ Pemisah ribuan: "." atau ","               │
 * └────────────────────────────┴────────────────────────────────────────────┘
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string'); // string, integer, boolean, json
            $table->string('group', 50)->default('general'); // general, accounting, tax, format
            $table->string('label', 150)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};