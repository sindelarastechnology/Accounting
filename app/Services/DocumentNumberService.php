<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DocumentNumberService
{
    /**
     * Generate document number with race condition protection
     *
     * @param string $type Document type (e.g., 'invoices', 'purchases')
     * @param string $prefix Document prefix (e.g., 'INV', 'PO')
     * @param string|null $date Transaction date (if null, uses now())
     * @return string Generated document number
     */
    public static function generate(string $type, string $prefix, ?string $date = null): string
    {
        $dateObj = $date ? Carbon::parse($date) : now();
        $year = (int) $dateObj->format('Y');
        $month = (int) $dateObj->format('m');

        return DB::transaction(function () use ($type, $prefix, $month, $year) {
            $counter = DB::table('document_counters')
                ->where('type', $type)
                ->where('year', $year)
                ->where('month', $month)
                ->lockForUpdate()
                ->first();

            if ($counter) {
                $lastNumber = $counter->last_number + 1;
                DB::table('document_counters')
                    ->where('id', $counter->id)
                    ->update(['last_number' => $lastNumber]);
            } else {
                $lastNumber = 1;
                DB::table('document_counters')
                    ->insert([
                        'type' => $type,
                        'year' => $year,
                        'month' => $month,
                        'last_number' => $lastNumber,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
            }

            $sequence = str_pad((string) $lastNumber, 5, '0', STR_PAD_LEFT);
            $monthPadded = str_pad((string) $month, 2, '0', STR_PAD_LEFT);

            return "{$prefix}/{$year}/{$monthPadded}/{$sequence}";
        });
    }
}
