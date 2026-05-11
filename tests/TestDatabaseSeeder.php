<?php

namespace Tests;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $now = now();

            // ==================== ACCOUNTS (minimal set) ====================
            $allAccounts = [
                // Asset
                ['1100-00-010', 'Kas Kecil',                  'asset',     'debit',  false, null],
                ['1200-00-010', 'Bank BCA',                   'asset',     'debit',  false, null],
                ['1300-00-020', 'Piutang Usaha',              'asset',     'debit',  false, null],
                ['1400-00-010', 'Persediaan 1',               'asset',     'debit',  false, null],
                ['1500-00-030', 'PPN Masukan',                'asset',     'debit',  false, null],
                ['1500-00-040', 'PPh 23 Dibayar Dimuka',      'asset',     'debit',  false, null],
                ['1700-00-030', 'Mesin dan Peralatan',        'asset',     'debit',  false, null],
                ['1700-00-021', 'Akumulasi Penyusutan Bangunan','asset',   'credit', false, null],

                // Liability
                ['2100-00-020', 'Hutang Usaha',               'liability', 'credit', false, null],
                ['2100-00-070', 'Hutang PPN Keluaran',        'liability', 'credit', false, null],
                ['2100-00-071', 'Hutang PPh 23',              'liability', 'credit', false, null],

                // Equity
                ['3100-00-020', 'Modal Disetor',              'equity',    'credit', false, null],
                ['3200-00-010', 'Laba ditahan',               'equity',    'credit', false, null],
                ['3200-00-020', 'Laba Tahun Berjalan',        'equity',    'credit', false, null],

                // Revenue
                ['4100-00-010', 'PENJUALAN BW',               'revenue',   'credit', false, null],
                ['4200-00-040', 'Penjualan Lain',             'revenue',   'credit', false, null],

                // COGS
                ['5100-00-010', 'PEMBELIAN BW',               'cogs',      'debit',  false, null],
                ['5200-00-010', 'Kerugian Piutang',           'cogs',      'debit',  false, null],
                ['5200-00-030', 'Pemakaian Barang',           'cogs',      'debit',  false, null],

                // Expense
                ['6110-00-010', 'Gaji Direksi dan Karyawan',  'expense',   'debit',  false, null],
                ['6110-00-030', 'Listrik, Air dan Telpon',    'expense',   'debit',  false, null],
                ['6600-00-010', 'Penyusutan Bangunan',        'expense',   'debit',  false, null],
            ];

            $inserted = [];
            foreach ($allAccounts as $acc) {
                [$code, $name, $category, $normalBalance, $isHeader, $parentCode] = $acc;
                $id = DB::table('accounts')->insertGetId([
                    'code'           => $code,
                    'name'           => $name,
                    'category'       => $category,
                    'normal_balance' => $normalBalance,
                    'parent_id'      => null,
                    'is_header'      => $isHeader,
                    'is_active'      => true,
                    'description'    => null,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]);
                $inserted[$code] = $id;
            }

            // ==================== SETTINGS ====================
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
                'fixed_asset_account_id'          => '1700-00-030',
                'gain_on_disposal_account_id'     => '4200-00-040',
                'loss_on_disposal_account_id'     => '5200-00-010',
                'expense_account_id'              => '6110-00-010',
                'accumulated_depreciation_account_id' => '1700-00-021',
                'cash_account_id'                 => '1100-00-010',
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

            // Prefix settings
            foreach (['invoice_prefix' => 'INV', 'purchase_prefix' => 'PO', 'journal_prefix' => 'JNL', 'payment_prefix' => 'PAY', 'expense_prefix' => 'EXP'] as $key => $value) {
                DB::table('settings')->updateOrInsert(
                    ['key' => $key],
                    ['value' => $value, 'type' => 'string', 'updated_at' => $now]
                );
            }

            // ==================== PERIODS ====================
            $year = (int) $now->format('Y');
            $currentMonth = (int) $now->format('n');
            for ($m = 1; $m <= $currentMonth + 1; $m++) {
                $monthName = \Carbon\Carbon::create($year, $m, 1)->locale('id')->isoFormat('MMMM');
                $endDay = \Carbon\Carbon::create($year, $m, 1)->endOfMonth()->format('Y-m-d');
                DB::table('periods')->insert([
                    'name'       => "{$monthName} {$year}",
                    'start_date' => sprintf('%d-%02d-01', $year, $m),
                    'end_date'   => $endDay,
                    'is_closed'  => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // ==================== TAX RULES ====================
            $ppnOutputId = $inserted['2100-00-070'];
            $ppnInputId  = $inserted['1500-00-030'];
            $pphPayableId = $inserted['2100-00-071'];

            DB::table('tax_rules')->insert([
                [
                    'code'              => 'PPN_11',
                    'name'              => 'PPN 11%',
                    'type'              => 'ppn',
                    'module'            => 'both',
                    'method'            => 'exclusive',
                    'rate'              => 11,
                    'debit_account_id'  => $ppnInputId,
                    'credit_account_id' => $ppnOutputId,
                    'is_default'        => true,
                    'is_active'         => true,
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ],
                [
                    'code'              => 'PPH23_2',
                    'name'              => 'PPh 23 (2%)',
                    'type'              => 'pph',
                    'module'            => 'purchase',
                    'method'            => 'withholding',
                    'rate'              => 2,
                    'debit_account_id'  => $pphPayableId,
                    'credit_account_id' => $pphPayableId,
                    'is_default'        => false,
                    'is_active'         => true,
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ],
            ]);
        });
    }
}
