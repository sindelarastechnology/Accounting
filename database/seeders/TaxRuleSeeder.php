<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaxRuleSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $ppnOutputId = DB::table('accounts')->where('code', '2100-00-070')->value('id');
        $ppnInputId = DB::table('accounts')->where('code', '1500-00-030')->value('id');
        $pphPayableId = DB::table('accounts')->where('code', '2100-00-071')->value('id');

        if (!$ppnOutputId || !$ppnInputId || !$pphPayableId) {
            $this->command->error('Akun pajak tidak ditemukan. Pastikan AccountSeeder sudah dijalankan.');
            return;
        }

        DB::table('tax_rules')->insert([
            [
                'code' => 'PPN_11',
                'name' => 'PPN 11%',
                'type' => 'ppn',
                'module' => 'both',
                'method' => 'exclusive',
                'rate' => 11.0000,
                'debit_account_id' => $ppnInputId,
                'credit_account_id' => $ppnOutputId,
                'is_default' => true,
                'is_active' => true,
                'description' => 'Pajak Pertambahan Nilai 11% untuk penjualan dan pembelian',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'PPH23_2',
                'name' => 'PPh Pasal 23 - Jasa 2%',
                'type' => 'pph',
                'module' => 'purchase',
                'method' => 'withholding',
                'rate' => 2.0000,
                'debit_account_id' => $pphPayableId,
                'credit_account_id' => $pphPayableId,
                'is_default' => false,
                'is_active' => true,
                'description' => 'Pemotongan PPh Pasal 23 atas jasa dengan tarif 2%',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
