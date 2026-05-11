<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $accounts = [
            // ==================== ASET (1xxx) ====================
            ['code' => '1000', 'name' => 'Aset Lancar', 'category' => 'asset', 'normal_balance' => 'debit', 'is_header' => true],
            ['code' => '1100', 'name' => 'Kas dan Setara Kas', 'category' => 'asset', 'normal_balance' => 'debit', 'is_header' => true],
            ['code' => '1101', 'name' => 'Kas Kecil', 'category' => 'asset', 'normal_balance' => 'debit', 'is_header' => false, 'parent_code' => '1100'],
            ['code' => '1102', 'name' => 'Kas di Bank', 'category' => 'asset', 'normal_balance' => 'debit', 'is_header' => false, 'parent_code' => '1100'],
            ['code' => '1200', 'name' => 'Piutang Usaha', 'category' => 'asset', 'normal_balance' => 'debit', 'is_header' => false, 'parent_code' => '1000'],
            ['code' => '1210', 'name' => 'Piutang Lainnya', 'category' => 'asset', 'normal_balance' => 'debit', 'is_header' => false, 'parent_code' => '1000'],
            ['code' => '1300', 'name' => 'Persediaan', 'category' => 'asset', 'normal_balance' => 'debit', 'is_header' => false, 'parent_code' => '1000'],
            ['code' => '1400', 'name' => 'Pembayaran di Muka', 'category' => 'asset', 'normal_balance' => 'debit', 'is_header' => false, 'parent_code' => '1000'],
            ['code' => '1500', 'name' => 'Aset Tetap', 'category' => 'asset', 'normal_balance' => 'debit', 'is_header' => true],
            ['code' => '1501', 'name' => 'Peralatan dan Mesin', 'category' => 'asset', 'normal_balance' => 'debit', 'is_header' => false, 'parent_code' => '1500'],
            ['code' => '1502', 'name' => 'Kendaraan', 'category' => 'asset', 'normal_balance' => 'debit', 'is_header' => false, 'parent_code' => '1500'],
            ['code' => '1510', 'name' => 'Akumulasi Penyusutan Peralatan', 'category' => 'asset', 'normal_balance' => 'credit', 'is_header' => false, 'parent_code' => '1500'],
            ['code' => '1520', 'name' => 'Akumulasi Penyusutan Kendaraan', 'category' => 'asset', 'normal_balance' => 'credit', 'is_header' => false, 'parent_code' => '1500'],
            ['code' => '1600', 'name' => 'PPN Masukan', 'category' => 'asset', 'normal_balance' => 'debit', 'is_header' => false, 'parent_code' => '1000'],

            // ==================== LIABILITAS (2xxx) ====================
            ['code' => '2000', 'name' => 'Liabilitas Jangka Pendek', 'category' => 'liability', 'normal_balance' => 'credit', 'is_header' => true],
            ['code' => '2100', 'name' => 'Hutang Usaha', 'category' => 'liability', 'normal_balance' => 'credit', 'is_header' => false, 'parent_code' => '2000'],
            ['code' => '2200', 'name' => 'Hutang PPN Keluaran', 'category' => 'liability', 'normal_balance' => 'credit', 'is_header' => false, 'parent_code' => '2000'],
            ['code' => '2300', 'name' => 'Hutang PPh Pasal 23', 'category' => 'liability', 'normal_balance' => 'credit', 'is_header' => false, 'parent_code' => '2000'],
            ['code' => '2310', 'name' => 'Hutang PPh Pasal 21', 'category' => 'liability', 'normal_balance' => 'credit', 'is_header' => false, 'parent_code' => '2000'],
            ['code' => '2320', 'name' => 'Hutang PPh Pasal 4(2)', 'category' => 'liability', 'normal_balance' => 'credit', 'is_header' => false, 'parent_code' => '2000'],
            ['code' => '2400', 'name' => 'Hutang Gaji', 'category' => 'liability', 'normal_balance' => 'credit', 'is_header' => false, 'parent_code' => '2000'],
            ['code' => '2500', 'name' => 'Hutang Bank Jangka Pendek', 'category' => 'liability', 'normal_balance' => 'credit', 'is_header' => false, 'parent_code' => '2000'],

            // ==================== EKUITAS (3xxx) ====================
            ['code' => '3000', 'name' => 'Ekuitas', 'category' => 'equity', 'normal_balance' => 'credit', 'is_header' => true],
            ['code' => '3100', 'name' => 'Modal Pemilik', 'category' => 'equity', 'normal_balance' => 'credit', 'is_header' => false, 'parent_code' => '3000'],
            ['code' => '3200', 'name' => 'Laba Ditahan', 'category' => 'equity', 'normal_balance' => 'credit', 'is_header' => false, 'parent_code' => '3000'],
            ['code' => '3300', 'name' => 'Ikhtisar Laba Rugi', 'category' => 'equity', 'normal_balance' => 'credit', 'is_header' => false, 'parent_code' => '3000'],
            ['code' => '3400', 'name' => 'Prive / Pengambilan Pribadi', 'category' => 'equity', 'normal_balance' => 'debit', 'is_header' => false, 'parent_code' => '3000'],

            // ==================== PENDAPATAN (4xxx) ====================
            ['code' => '4000', 'name' => 'Pendapatan', 'category' => 'revenue', 'normal_balance' => 'credit', 'is_header' => true],
            ['code' => '4100', 'name' => 'Pendapatan Penjualan', 'category' => 'revenue', 'normal_balance' => 'credit', 'is_header' => false, 'parent_code' => '4000'],
            ['code' => '4200', 'name' => 'Pendapatan Jasa', 'category' => 'revenue', 'normal_balance' => 'credit', 'is_header' => false, 'parent_code' => '4000'],
            ['code' => '4300', 'name' => 'Pendapatan Lain-lain', 'category' => 'revenue', 'normal_balance' => 'credit', 'is_header' => false, 'parent_code' => '4000'],
            ['code' => '4400', 'name' => 'Pendapatan Selisih Stok', 'category' => 'revenue', 'normal_balance' => 'credit', 'is_header' => false, 'parent_code' => '4000'],

            // ==================== HPP (5xxx) ====================
            ['code' => '5000', 'name' => 'Harga Pokok Penjualan', 'category' => 'cogs', 'normal_balance' => 'debit', 'is_header' => true],
            ['code' => '5100', 'name' => 'HPP Barang Dagang', 'category' => 'cogs', 'normal_balance' => 'debit', 'is_header' => false, 'parent_code' => '5000'],
            ['code' => '5200', 'name' => 'HPP Jasa', 'category' => 'cogs', 'normal_balance' => 'debit', 'is_header' => false, 'parent_code' => '5000'],

            // ==================== BEBAN (6xxx) ====================
            ['code' => '6000', 'name' => 'Beban Operasional', 'category' => 'expense', 'normal_balance' => 'debit', 'is_header' => true],
            ['code' => '6100', 'name' => 'Beban Gaji dan Upah', 'category' => 'expense', 'normal_balance' => 'debit', 'is_header' => false, 'parent_code' => '6000'],
            ['code' => '6200', 'name' => 'Beban Sewa', 'category' => 'expense', 'normal_balance' => 'debit', 'is_header' => false, 'parent_code' => '6000'],
            ['code' => '6300', 'name' => 'Beban Listrik, Air, dan Telepon', 'category' => 'expense', 'normal_balance' => 'debit', 'is_header' => false, 'parent_code' => '6000'],
            ['code' => '6400', 'name' => 'Beban Perlengkapan', 'category' => 'expense', 'normal_balance' => 'debit', 'is_header' => false, 'parent_code' => '6000'],
            ['code' => '6500', 'name' => 'Beban Transportasi', 'category' => 'expense', 'normal_balance' => 'debit', 'is_header' => false, 'parent_code' => '6000'],
            ['code' => '6600', 'name' => 'Beban Penyusutan Aset', 'category' => 'expense', 'normal_balance' => 'debit', 'is_header' => false, 'parent_code' => '6000'],
            ['code' => '6700', 'name' => 'Beban Asuransi', 'category' => 'expense', 'normal_balance' => 'debit', 'is_header' => false, 'parent_code' => '6000'],
            ['code' => '6800', 'name' => 'Beban Administrasi dan Umum', 'category' => 'expense', 'normal_balance' => 'debit', 'is_header' => false, 'parent_code' => '6000'],
            ['code' => '6900', 'name' => 'Beban Selisih Stok', 'category' => 'expense', 'normal_balance' => 'debit', 'is_header' => false, 'parent_code' => '6000'],
        ];

        $parentIdMap = [];

        foreach ($accounts as $account) {
            $parentCode = $account['parent_code'] ?? null;
            unset($account['parent_code']);

            $parentId = null;
            if ($parentCode && isset($parentIdMap[$parentCode])) {
                $parentId = $parentIdMap[$parentCode];
            }

            $account['parent_id'] = $parentId;
            $account['description'] = null;
            $account['created_at'] = $now;
            $account['updated_at'] = $now;

            $id = DB::table('accounts')->insertGetId($account);
            $parentIdMap[$account['code']] = $id;
        }
    }
}
