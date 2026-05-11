<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChartOfAccountSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('journal_lines')->exists() || DB::table('journals')->exists()) {
            $this->command->warn('Data transaksi sudah ada. Lewati rebuild COA untuk menjaga integritas referensi.');
            $this->command->warn('Jalankan: php artisan db:seed --class=ChartOfAccountSeeder --force jika yakin.');
            return;
        }

        DB::transaction(function () {
            $now = now();

            // ================================================================
            // DEFINE ALL ACCOUNTS
            // ================================================================
            // Struktur: [code, name, category, normal_balance, is_header, parent_code]
            // is_header = true  → kode berakhir -00-000 (header/grup)
            // is_header = false → kode detail (leaf account)
            // ================================================================

            $allAccounts = [
                // ==================== 1000 — HARTA (Asset) ====================
                ['1000-00-000', 'Harta',                          'asset',     'debit',  true,  null],
                  ['1100-00-000', 'Kas',                          'asset',     'debit',  true,  '1000-00-000'],
                    ['1100-00-010', 'Kas Kecil',                  'asset',     'debit',  false, '1100-00-000'],
                    ['1100-00-020', 'Kas',                        'asset',     'debit',  false, '1100-00-000'],
                  ['1200-00-000', 'Bank',                         'asset',     'debit',  true,  '1000-00-000'],
                    ['1200-00-010', 'Bank BCA',                   'asset',     'debit',  false, '1200-00-000'],
                    ['1200-00-020', 'Bank Lain',                  'asset',     'debit',  false, '1200-00-000'],
                  ['1300-00-000', 'Piutang Dagang',               'asset',     'debit',  true,  '1000-00-000'],
                    ['1300-00-010', 'Piutang Giro',               'asset',     'debit',  false, '1300-00-000'],
                    ['1300-00-020', 'Piutang Usaha',              'asset',     'debit',  false, '1300-00-000'],
                    ['1300-00-021', 'Piutang Sementara',          'asset',     'debit',  false, '1300-00-000'],
                    ['1300-00-030', 'Piutang Usaha (USD)',        'asset',     'debit',  false, '1300-00-000'],
                    ['1300-00-031', 'Piutang Sementara (USD)',    'asset',     'debit',  false, '1300-00-000'],
                    ['1300-00-040', 'Cadangan Kerugian Piutang',  'asset',     'credit', false, '1300-00-000'],
                    ['1300-00-050', 'Piutang Non Usaha',          'asset',     'debit',  false, '1300-00-000'],
                    ['1300-00-098', 'Uang Muka Pembelian',        'asset',     'debit',  false, '1300-00-000'],
                    ['1300-00-099', 'Uang Muka Pembelian (USD)',  'asset',     'debit',  false, '1300-00-000'],
                  ['1400-00-000', 'Persediaan',                   'asset',     'debit',  true,  '1000-00-000'],
                    ['1400-00-010', 'Persediaan 1',               'asset',     'debit',  false, '1400-00-000'],
                    ['1400-00-020', 'Persediaan 2',               'asset',     'debit',  false, '1400-00-000'],
                    ['1400-00-030', 'Persediaan 3',               'asset',     'debit',  false, '1400-00-000'],
                    ['1400-00-040', 'Persediaan 4',               'asset',     'debit',  false, '1400-00-000'],
                    ['1400-00-098', 'Persediaan Dalam Perjalanan Beli', 'asset', 'debit', false, '1400-00-000'],
                    ['1400-00-099', 'Persediaan Dalam Perjalanan Jual',  'asset', 'debit', false, '1400-00-000'],
                  ['1500-00-000', 'Biaya Dibayar Dimuka',         'asset',     'debit',  true,  '1000-00-000'],
                    ['1500-00-010', 'Pajak Dibayar di Muka',      'asset',     'debit',  false, '1500-00-000'],
                    ['1500-00-020', 'Asuransi Dibayar di Muka',   'asset',     'debit',  false, '1500-00-000'],
                    ['1500-00-030', 'PPN Masukan',                'asset',     'debit',  false, '1500-00-000'],
                    ['1500-00-040', 'PPh 23 Dibayar Dimuka',      'asset',     'debit',  false, '1500-00-000'],
                  ['1600-00-000', 'Investasi Jangka Panjang',     'asset',     'debit',  true,  '1000-00-000'],
                    ['1600-00-010', 'Investasi Saham',            'asset',     'debit',  false, '1600-00-000'],
                    ['1600-00-020', 'Investasi Obligasi',         'asset',     'debit',  false, '1600-00-000'],
                  ['1700-00-000', 'Harta Tetap Berwujud',         'asset',     'debit',  true,  '1000-00-000'],
                    ['1700-00-010', 'Tanah',                      'asset',     'debit',  false, '1700-00-000'],
                    ['1700-00-020', 'Bangunan',                   'asset',     'debit',  false, '1700-00-000'],
                    ['1700-00-021', 'Akumulasi Penyusutan Bangunan',         'asset', 'credit', false, '1700-00-000'],
                    ['1700-00-030', 'Mesin dan Peralatan',        'asset',     'debit',  false, '1700-00-000'],
                    ['1700-00-031', 'Akumulasi Penyusutan Mesin dan Peralatan', 'asset', 'credit', false, '1700-00-000'],
                    ['1700-00-040', 'Mebel dan Alat Tulis Kantor','asset',     'debit',  false, '1700-00-000'],
                    ['1700-00-041', 'Akumulasi Penyusutan Mebel dan ATK',    'asset', 'credit', false, '1700-00-000'],
                    ['1700-00-050', 'Kendaraan',                  'asset',     'debit',  false, '1700-00-000'],
                    ['1700-00-051', 'Akumulasi Penyusutan Kendaraan',         'asset', 'credit', false, '1700-00-000'],
                    ['1700-00-070', 'Harta Lainnya',              'asset',     'debit',  false, '1700-00-000'],
                    ['1700-00-071', 'Akumulasi Penyusutan Harta Lainnya',     'asset', 'credit', false, '1700-00-000'],
                  ['1800-00-000', 'Harta Tetap Tidak Berwujud',   'asset',     'debit',  true,  '1000-00-000'],
                    ['1800-00-010', 'Hak Merek',                  'asset',     'debit',  false, '1800-00-000'],
                    ['1800-00-020', 'Hak Cipta',                  'asset',     'debit',  false, '1800-00-000'],
                    ['1800-00-030', 'Good Will',                  'asset',     'debit',  false, '1800-00-000'],
                  ['1900-00-000', 'Harta Lainnya',                'asset',     'debit',  true,  '1000-00-000'],
                    ['1900-00-020', 'Biaya Pra Operasi dan Operasi',        'asset', 'debit', false, '1900-00-000'],
                    ['1900-00-021', 'Akumulasi Amortisasi Pra Operasi dan Operasi', 'asset', 'credit', false, '1900-00-000'],

                // ==================== 2000 — KEWAJIBAN (Liability) ====================
                ['2000-00-000', 'Kewajiban',                     'liability', 'credit', true,  null],
                  ['2100-00-000', 'Hutang Lancar',                'liability', 'credit', true,  '2000-00-000'],
                    ['2100-00-010', 'Wesel Bayar',                'liability', 'credit', false, '2100-00-000'],
                    ['2100-00-015', 'Hutang Giro',                'liability', 'credit', false, '2100-00-000'],
                    ['2100-00-020', 'Hutang Usaha',               'liability', 'credit', false, '2100-00-000'],
                    ['2100-00-025', 'Hutang BHP USO',             'liability', 'credit', false, '2100-00-000'],
                    ['2100-00-030', 'Hutang Konsinyasi',          'liability', 'credit', false, '2100-00-000'],
                    ['2100-00-040', 'Uang Muka Penjualan',        'liability', 'credit', false, '2100-00-000'],
                    ['2100-00-055', 'Hutang Deviden',             'liability', 'credit', false, '2100-00-000'],
                    ['2100-00-060', 'Hutang Bunga',               'liability', 'credit', false, '2100-00-000'],
                    ['2100-00-065', 'Biaya yang Masih Harus Dibayar', 'liability', 'credit', false, '2100-00-000'],
                    ['2100-00-075', 'Kartu Kredit',               'liability', 'credit', false, '2100-00-000'],
                    ['2100-00-080', 'Hutang Pajak Penjualan',     'liability', 'credit', false, '2100-00-000'],
                    ['2100-00-082', 'Hutang Komisi Penjualan',    'liability', 'credit', false, '2100-00-000'],
                    ['2100-00-085', 'Hutang Gaji',                'liability', 'credit', false, '2100-00-000'],
                    ['2100-00-098', 'Uang Muka Penjualan',        'liability', 'credit', false, '2100-00-000'],
                    ['2100-00-099', 'Uang Muka Penjualan (USD)',  'liability', 'credit', false, '2100-00-000'],
                    // Tax payable accounts (system)
                    ['2100-00-070', 'Hutang PPN Keluaran',        'liability', 'credit', false, '2100-00-000'],
                    ['2100-00-071', 'Hutang PPh 23',              'liability', 'credit', false, '2100-00-000'],
                    ['2100-00-072', 'Hutang PPh 21',              'liability', 'credit', false, '2100-00-000'],
                    ['2100-00-073', 'Hutang PPh Pasal 4 Ayat 2',  'liability', 'credit', false, '2100-00-000'],
                  ['2200-00-000', 'Pendapatan yang diterima di muka', 'liability', 'credit', true, '2000-00-000'],
                    ['2200-00-010', 'Sewa Diterima di Muka',      'liability', 'credit', false, '2200-00-000'],
                  ['2300-00-000', 'Hutang Jangka Panjang',        'liability', 'credit', true,  '2000-00-000'],
                    ['2300-00-010', 'UTANG PEMBIAYAAN',           'liability', 'credit', false, '2300-00-000'],
                    ['2300-00-020', 'Hutang Bank',                'liability', 'credit', false, '2300-00-000'],

                // ==================== 3000 — MODAL (Equity) ====================
                ['3000-00-000', 'Modal',                          'equity',    'credit', true,  null],
                  ['3100-00-000', 'Modal',                        'equity',    'credit', true,  '3000-00-000'],
                    ['3100-00-010', 'Saham Preferen',             'equity',    'credit', false, '3100-00-000'],
                    ['3100-00-020', 'Modal Disetor',              'equity',    'credit', false, '3100-00-000'],
                  ['3200-00-000', 'Laba',                         'equity',    'credit', true,  '3000-00-000'],
                    ['3200-00-010', 'Laba ditahan',               'equity',    'credit', false, '3200-00-000'],
                    ['3200-00-020', 'Laba Tahun Berjalan',        'equity',    'credit', false, '3200-00-000'],
                    ['3200-00-099', 'Historical Balancing',       'equity',    'credit', false, '3200-00-000'],

                // ==================== 4000 — PENDAPATAN (Revenue) ====================
                ['4000-00-000', 'Pendapatan',                     'revenue',   'credit', true,  null],
                  ['4100-00-000', 'Pendapatan Usaha',             'revenue',   'credit', true,  '4000-00-000'],
                    ['4100-00-010', 'PENJUALAN BW',               'revenue',   'credit', false, '4100-00-000'],
                    ['4100-00-020', 'REGESTRASI',                 'revenue',   'credit', false, '4100-00-000'],
                    ['4100-00-030', 'Penjualan Produk 3',         'revenue',   'credit', false, '4100-00-000'],
                  ['4200-00-000', 'Pendapatan Lain',              'revenue',   'credit', true,  '4000-00-000'],
                    ['4200-00-040', 'Penjualan Lain',             'revenue',   'credit', false, '4200-00-000'],
                    ['4200-00-070', 'Potongan Penjualan',         'revenue',   'credit', false, '4200-00-000'],
                    ['4200-00-080', 'Pendapatan Denda Keterlambatan', 'revenue', 'credit', false, '4200-00-000'],
                    ['4200-00-090', 'Pendapatan atas Pengantaran','revenue',   'credit', false, '4200-00-000'],
                  ['4300-00-000', 'Pendapatan Lain-lain',         'revenue',   'credit', true,  '4000-00-000'],

                // ==================== 5000 — BIAYA ATAS PENDAPATAN (COGS/HPP) ====================
                ['5000-00-000', 'Biaya atas Pendapatan',          'cogs',      'debit',  true,  null],
                  ['5100-00-000', 'Biaya Produksi',               'cogs',      'debit',  true,  '5000-00-000'],
                    ['5100-00-010', 'PEMBELIAN BW',               'cogs',      'debit',  false, '5100-00-000'],
                    ['5100-00-020', 'Biaya 2',                    'cogs',      'debit',  false, '5100-00-000'],
                    ['5100-00-030', 'Biaya 3',                    'cogs',      'debit',  false, '5100-00-000'],
                    ['5100-00-040', 'Komisi Penjualan',           'cogs',      'debit',  false, '5100-00-000'],
                    ['5100-00-070', 'PSB',                        'cogs',      'debit',  false, '5100-00-000'],
                    ['5100-00-080', 'KERJASAMA',                  'cogs',      'debit',  false, '5100-00-000'],
                  ['5200-00-000', 'Biaya Lain',                   'cogs',      'debit',  true,  '5000-00-000'],
                    ['5200-00-010', 'Kerugian Piutang',           'cogs',      'debit',  false, '5200-00-000'],
                    ['5200-00-020', 'Biaya Denda Keterlambatan',  'cogs',      'debit',  false, '5200-00-000'],
                    ['5200-00-030', 'Pemakaian Barang',           'cogs',      'debit',  false, '5200-00-000'],

                // ==================== 6000 — PENGELUARAN OPERASIONAL (Expense) ====================
                ['6100-00-000', 'Pengeluaran Operasional',        'expense',   'debit',  true,  null],
                  ['6110-00-000', 'Biaya Operasional',            'expense',   'debit',  true,  '6100-00-000'],
                    ['6110-00-010', 'Gaji Direksi dan Karyawan',  'expense',   'debit',  false, '6110-00-000'],
                    ['6110-00-030', 'Listrik, Air dan Telpon',    'expense',   'debit',  false, '6110-00-000'],
                    ['6110-00-031', 'Admin Bank',                 'expense',   'debit',  false, '6110-00-000'],
                    ['6110-00-032', 'Konsumsi',                   'expense',   'debit',  false, '6110-00-000'],
                    ['6110-00-033', 'Ongkir',                     'expense',   'debit',  false, '6110-00-000'],
                    ['6110-00-050', 'Promosi dan Iklan',          'expense',   'debit',  false, '6110-00-000'],
                    ['6110-00-060', 'Administrasi Kantor',        'expense',   'debit',  false, '6110-00-000'],
                    ['6110-00-070', 'OPERASIONAL',                'expense',   'debit',  false, '6110-00-000'],
                    ['6110-00-080', 'LAIN-LAIN',                  'expense',   'debit',  false, '6110-00-000'],
                  ['6600-00-000', 'Biaya Non Operasional',        'expense',   'debit',  true,  '6100-00-000'],
                    ['6600-00-010', 'Penyusutan Bangunan',        'expense',   'debit',  false, '6600-00-000'],
                    ['6600-00-011', 'Penyusutan Mesin dan Peralatan', 'expense', 'debit', false, '6600-00-000'],
                    ['6600-00-012', 'Penyusutan Mebel dan ATK',   'expense',   'debit',  false, '6600-00-000'],
                    ['6600-00-013', 'Penyusutan Kendaraan',       'expense',   'debit',  false, '6600-00-000'],
                    ['6600-00-015', 'Penyusutan Harta Lainnya',   'expense',   'debit',  false, '6600-00-000'],
                    ['6600-00-016', 'Amortisasi Pra Operasi dan Operasi', 'expense', 'debit', false, '6600-00-000'],
            ];

            // ================================================================
            // INSERT ACCOUNTS WITH PARENT RESOLUTION
            // ================================================================
            $inserted = []; // code => id

            foreach ($allAccounts as $acc) {
                [$code, $name, $category, $normalBalance, $isHeader, $parentCode] = $acc;

                $parentId = $parentCode ? ($inserted[$parentCode] ?? null) : null;

                $id = DB::table('accounts')->insertGetId([
                    'code'           => $code,
                    'name'           => $name,
                    'category'       => $category,
                    'normal_balance' => $normalBalance,
                    'parent_id'      => $parentId,
                    'is_header'      => $isHeader,
                    'is_active'      => true,
                    'description'    => null,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]);

                $inserted[$code] = $id;
            }

            // ================================================================
            // UPDATE SETTINGS WITH NEW ACCOUNT IDs
            // ================================================================
            $settingMap = [
                'ar_account_id'                   => '1300-00-020',
                'ap_account_id'                   => '2100-00-020',
                'revenue_account_id'              => '4100-00-010',
                'cogs_account_id'                 => '5100-00-010',
                'inventory_account_id'            => '1400-00-010',
                'equity_account_id'               => '3100-00-020',
                'retained_earnings_id'            => '3200-00-010',
                'income_summary_id'               => '3200-00-020',
                'ppn_output_account_id'           => '2100-00-070',
                'ppn_input_account_id'            => '1500-00-030',
                'pph_payable_account_id'          => '2100-00-071',
                'pph_prepaid_account_id'          => '1500-00-040',
                'accumulated_depreciation_account_id' => '1700-00-021',
                'stock_gain_account_id'           => '4200-00-040',
                'stock_loss_account_id'           => '5200-00-030',
                'fixed_asset_account_id'          => '1700-00-030',
                'gain_on_disposal_account_id'     => '4200-00-040',
                'loss_on_disposal_account_id'     => '5200-00-010',
                'expense_account_id'              => '6110-00-010',
            ];

            foreach ($settingMap as $key => $code) {
                $accountId = $inserted[$code] ?? null;
                if ($accountId) {
                    DB::table('settings')->updateOrInsert(
                        ['key' => $key],
                        [
                            'value'      => (string) $accountId,
                            'type'       => 'integer',
                            'updated_at' => $now,
                        ]
                    );
                }
            }

            // ================================================================
            // UPDATE TAX RULES with new account IDs
            // ================================================================
            $ppnOutputId = $inserted['2100-00-070'] ?? null;
            $ppnInputId  = $inserted['1500-00-030'] ?? null;
            $pphPayableId = $inserted['2100-00-071'] ?? null;

            if ($ppnOutputId && $ppnInputId && $pphPayableId) {
                DB::table('tax_rules')
                    ->where('code', 'PPN_11')
                    ->update([
                        'debit_account_id'  => $ppnInputId,
                        'credit_account_id' => $ppnOutputId,
                        'updated_at'        => $now,
                    ]);

                DB::table('tax_rules')
                    ->where('code', 'PPH23_2')
                    ->update([
                        'debit_account_id'  => $pphPayableId,
                        'credit_account_id' => $pphPayableId,
                        'updated_at'        => $now,
                    ]);
            }

            $this->command->info('Chart of Account berhasil di-rebuild.');
            $this->command->info('Total akun: ' . count($allAccounts));
        });
    }
}
