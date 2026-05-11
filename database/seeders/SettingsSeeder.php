<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $accountIds = DB::table('accounts')->pluck('id', 'code')->toArray();

        $settings = [
            // ==================== Accounting ====================
            [
                'key' => 'ar_account_id',
                'value' => $accountIds['1300-00-020'] ?? null,
                'type' => 'integer',
                'group' => 'accounting',
                'label' => 'Akun Piutang Usaha Default',
                'description' => 'Akun piutang yang digunakan saat membuat invoice',
            ],
            [
                'key' => 'ap_account_id',
                'value' => $accountIds['2100-00-020'] ?? null,
                'type' => 'integer',
                'group' => 'accounting',
                'label' => 'Akun Hutang Usaha Default',
                'description' => 'Akun hutang yang digunakan saat membuat purchase order',
            ],
            [
                'key' => 'revenue_account_id',
                'value' => $accountIds['4100-00-010'] ?? null,
                'type' => 'integer',
                'group' => 'accounting',
                'label' => 'Akun Pendapatan Default',
                'description' => 'Akun pendapatan utama untuk penjualan',
            ],
            [
                'key' => 'cogs_account_id',
                'value' => $accountIds['5100-00-010'] ?? null,
                'type' => 'integer',
                'group' => 'accounting',
                'label' => 'Akun HPP Default',
                'description' => 'Akun harga pokok penjualan untuk barang',
            ],
            [
                'key' => 'inventory_account_id',
                'value' => $accountIds['1400-00-010'] ?? null,
                'type' => 'integer',
                'group' => 'accounting',
                'label' => 'Akun Persediaan Default',
                'description' => 'Akun persediaan barang dagang',
            ],
            [
                'key' => 'equity_account_id',
                'value' => $accountIds['3100-00-020'] ?? null,
                'type' => 'integer',
                'group' => 'accounting',
                'label' => 'Akun Modal Default',
                'description' => 'Akun modal pemilik',
            ],
            [
                'key' => 'retained_earnings_id',
                'value' => $accountIds['3200-00-010'] ?? null,
                'type' => 'integer',
                'group' => 'accounting',
                'label' => 'Akun Laba Ditahan',
                'description' => 'Akun laba ditahan untuk closing periode',
            ],
            [
                'key' => 'income_summary_id',
                'value' => $accountIds['3200-00-020'] ?? null,
                'type' => 'integer',
                'group' => 'accounting',
                'label' => 'Akun Ikhtisar Laba Rugi',
                'description' => 'Akun ikhtisar L/R untuk proses penutupan periode',
            ],

            // ==================== Tax ====================
            [
                'key' => 'ppn_output_account_id',
                'value' => $accountIds['2100-00-070'] ?? null,
                'type' => 'integer',
                'group' => 'tax',
                'label' => 'Akun PPN Keluaran',
                'description' => 'Akun hutang PPN dari penjualan',
            ],
            [
                'key' => 'ppn_input_account_id',
                'value' => $accountIds['1500-00-030'] ?? null,
                'type' => 'integer',
                'group' => 'tax',
                'label' => 'Akun PPN Masukan',
                'description' => 'Akun PPN dari pembelian',
            ],
            [
                'key' => 'pph_payable_account_id',
                'value' => $accountIds['2100-00-071'] ?? null,
                'type' => 'integer',
                'group' => 'tax',
                'label' => 'Akun Hutang PPh',
                'description' => 'Akun hutang pajak penghasilan yang dipotong',
            ],
            [
                'key' => 'pph_prepaid_account_id',
                'value' => $accountIds['1500-00-040'] ?? null,
                'type' => 'integer',
                'group' => 'tax',
                'label' => 'Akun PPh Dibayar Dimuka',
                'description' => 'Akun PPh pasal 23 yang dibayar dimuka',
            ],
            [
                'key' => 'accumulated_depreciation_account_id',
                'value' => $accountIds['1700-00-021'] ?? null,
                'type' => 'integer',
                'group' => 'accounting',
                'label' => 'Akun Akumulasi Penyusutan Default',
                'description' => 'Akun default untuk akumulasi penyusutan aset tetap',
            ],

            // ==================== Inventory ====================
            [
                'key' => 'stock_gain_account_id',
                'value' => $accountIds['4200-00-040'] ?? null,
                'type' => 'integer',
                'group' => 'accounting',
                'label' => 'Akun Pendapatan Selisih Stok',
                'description' => 'Akun untuk mencatat kelebihan stok saat opname',
            ],
            [
                'key' => 'stock_loss_account_id',
                'value' => $accountIds['5200-00-030'] ?? null,
                'type' => 'integer',
                'group' => 'accounting',
                'label' => 'Akun Beban Selisih Stok',
                'description' => 'Akun untuk mencatat kekurangan stok saat opname',
            ],

            // ==================== Numbering Format ====================
            [
                'key' => 'invoice_prefix',
                'value' => 'INV',
                'type' => 'string',
                'group' => 'format',
                'label' => 'Prefix Nomor Invoice',
                'description' => 'Awalan nomor faktur penjualan',
            ],
            [
                'key' => 'purchase_prefix',
                'value' => 'PO',
                'type' => 'string',
                'group' => 'format',
                'label' => 'Prefix Nomor Pembelian',
                'description' => 'Awalan nomor purchase order',
            ],
            [
                'key' => 'journal_prefix',
                'value' => 'JNL',
                'type' => 'string',
                'group' => 'format',
                'label' => 'Prefix Nomor Jurnal',
                'description' => 'Awalan nomor jurnal umum',
            ],
            [
                'key' => 'payment_prefix',
                'value' => 'PAY',
                'type' => 'string',
                'group' => 'format',
                'label' => 'Prefix Nomor Pembayaran',
                'description' => 'Awalan nomor pembayaran',
            ],
            [
                'key' => 'expense_prefix',
                'value' => 'EXP',
                'type' => 'string',
                'group' => 'format',
                'label' => 'Prefix Nomor Beban',
                'description' => 'Awalan nomor pencatatan beban',
            ],
            [
                'key' => 'opname_prefix',
                'value' => 'SOP',
                'type' => 'string',
                'group' => 'format',
                'label' => 'Prefix Stock Opname',
                'description' => 'Awalan nomor stock opname',
            ],

            // ==================== Currency ====================
            [
                'key' => 'currency_code',
                'value' => 'IDR',
                'type' => 'string',
                'group' => 'format',
                'label' => 'Kode Mata Uang',
                'description' => 'Kode mata uang ISO 4217',
            ],
            [
                'key' => 'currency_symbol',
                'value' => 'Rp',
                'type' => 'string',
                'group' => 'format',
                'label' => 'Simbol Mata Uang',
                'description' => 'Simbol yang ditampilkan di tampilan',
            ],
            [
                'key' => 'decimal_separator',
                'value' => ',',
                'type' => 'string',
                'group' => 'format',
                'label' => 'Pemisah Desimal',
                'description' => 'Karakter pemisah angka desimal',
            ],
            [
                'key' => 'thousand_separator',
                'value' => '.',
                'type' => 'string',
                'group' => 'format',
                'label' => 'Pemisah Ribuan',
                'description' => 'Karakter pemisah ribuan',
            ],

            // ==================== Tax Default ====================
            [
                'key' => 'default_tax_rate',
                'value' => '11',
                'type' => 'integer',
                'group' => 'tax',
                'label' => 'Tarif Pajak Default (%)',
                'description' => 'Persentase pajak default untuk transaksi',
            ],

            // ==================== Company ====================
            [
                'key' => 'company_name',
                'value' => 'Onezie Accounting',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Nama Perusahaan',
                'description' => 'Nama resmi perusahaan',
            ],
            [
                'key' => 'company_address',
                'value' => '',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Alamat Perusahaan',
                'description' => 'Alamat lengkap perusahaan',
            ],
            [
                'key' => 'company_phone',
                'value' => '',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Telepon Perusahaan',
                'description' => 'Nomor telepon perusahaan',
            ],
            [
                'key' => 'company_tax_number',
                'value' => '',
                'type' => 'string',
                'group' => 'general',
                'label' => 'NPWP Perusahaan',
                'description' => 'Nomor Pokok Wajib Pajak',
            ],
        ];

        foreach ($settings as $setting) {
            $setting['created_at'] = $now;
            $setting['updated_at'] = $now;
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
