<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PeriodSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $year = (int) $now->format('Y');

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        foreach ($months as $month => $name) {
            DB::table('periods')->insert([
                'name' => "{$name} {$year}",
                'start_date' => "{$year}-{$month}-01",
                'end_date' => "{$year}-{$month}-" . date('t', mktime(0, 0, 0, $month, 1, $year)),
                'is_closed' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
