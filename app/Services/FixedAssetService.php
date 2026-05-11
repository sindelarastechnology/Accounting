<?php

namespace App\Services;

use App\Exceptions\InvalidAccountException;
use App\Exceptions\PeriodClosedException;
use App\Helpers\AccountResolver;
use App\Models\Account;
use App\Models\FixedAsset;
use App\Models\Journal;
use App\Models\Period;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FixedAssetService
{
    public static function calculateMonthlyDepreciation(FixedAsset $asset): float
    {
        if ($asset->is_fully_depreciated || $asset->status !== 'active') {
            return 0;
        }

        $method = $asset->depreciation_method;
        $cost = (float) $asset->acquisition_cost;
        $salvage = (float) $asset->salvage_value;
        $years = $asset->useful_life_years;
        $months = $years * 12;
        $accumulated = (float) $asset->accumulated_depreciation;

        if ($method === 'straight_line') {
            return ($cost - $salvage) / $months;
        }

        if ($method === 'double_declining') {
            $rate = (2 / $years);
            $depreciableBase = $cost - $salvage;

            if ($depreciableBase <= 0) {
                return 0;
            }

            $elapsedMonths = max(0, Carbon::parse($asset->acquisition_date)->diffInMonths(Carbon::now()));
            $monthInCurrentYear = ($elapsedMonths % 12) + 1;
            $completedYears = (int) floor($elapsedMonths / 12);

            $bookValueStartOfYear = $cost;
            for ($y = 0; $y < $completedYears; $y++) {
                $yearDepreciation = $bookValueStartOfYear * $rate;
                if ($bookValueStartOfYear - $yearDepreciation < $salvage) {
                    $yearDepreciation = $bookValueStartOfYear - $salvage;
                }
                $bookValueStartOfYear -= $yearDepreciation;
            }

            $annualDepreciation = $bookValueStartOfYear * $rate;
            if ($bookValueStartOfYear - $annualDepreciation < $salvage) {
                $annualDepreciation = max(0, $bookValueStartOfYear - $salvage);
            }

            $monthlyDepreciation = $annualDepreciation / 12;

            $remainingDepreciable = ($bookValueStartOfYear - $salvage) - ($monthlyDepreciation * ($monthInCurrentYear - 1));
            if ($remainingDepreciable < $monthlyDepreciation) {
                $monthlyDepreciation = max(0, $remainingDepreciable);
            }

            return max(0, $monthlyDepreciation);
        }

        if ($method === 'sum_of_years') {
            $sumOfYears = ($years * ($years + 1)) / 2;
            $elapsedMonths = Carbon::parse($asset->acquisition_date)->diffInMonths(Carbon::now());
            $currentYear = floor($elapsedMonths / 12) + 1;
            $remainingYears = $years - $currentYear + 1;

            $annualDepreciation = ($cost - $salvage) * ($remainingYears / $sumOfYears);
            $monthlyDepreciation = $annualDepreciation / 12;

            return max(0, $monthlyDepreciation);
        }

        return 0;
    }

    public static function recordDepreciation(FixedAsset $asset, ?string $date = null): Journal
    {
        if ($asset->status !== 'active') {
            throw new InvalidAccountException("Aset '{$asset->name}' tidak aktif.");
        }

        if ($asset->is_fully_depreciated) {
            throw new InvalidAccountException("Aset '{$asset->name}' sudah disusutkan sepenuhnya.");
        }

        if (!$asset->depreciation_account_id) {
            throw new InvalidAccountException('Akun beban penyusutan untuk aset ini belum diatur.');
        }

        if (!$asset->asset_account_id) {
            throw new InvalidAccountException('Akun aset tetap untuk aset ini belum diatur.');
        }

        $accumDeprId = $asset->accumulated_depreciation_account_id
            ?? Account::where('code', '1700-00-021')->value('id');

        if (!$accumDeprId) {
            throw new InvalidAccountException('Akun akumulasi penyusutan untuk aset ini belum diatur.');
        }

        $depreciationDate = $date ? Carbon::parse($date) : Carbon::now();

        // Check if already recorded for this month
        $existingJournal = Journal::where('ref_type', 'fixed_assets')
            ->where('ref_id', $asset->id)
            ->whereYear('date', $depreciationDate->year)
            ->whereMonth('date', $depreciationDate->month)
            ->whereNotIn('type', ['void'])
            ->first();

        if ($existingJournal) {
            throw new InvalidAccountException('Penyusutan untuk bulan ini sudah dicatat.');
        }

        $period = Period::whereYear('start_date', $depreciationDate->year)
            ->whereMonth('start_date', $depreciationDate->month)
            ->first();

        if (!$period) {
            throw new InvalidAccountException('Periode akuntansi tidak ditemukan.');
        }

        if ($period->is_closed) {
            throw new PeriodClosedException();
        }

        $monthlyDepreciation = self::calculateMonthlyDepreciation($asset);

        if ($monthlyDepreciation <= 0) {
            throw new InvalidAccountException('Tidak ada penyusutan yang perlu dicatat.');
        }

        return DB::transaction(function () use ($asset, $depreciationDate, $monthlyDepreciation, $period, $accumDeprId) {
            $journalLines = [
                [
                    'account_id' => $asset->depreciation_account_id,
                    'debit_amount' => $monthlyDepreciation,
                    'credit_amount' => 0,
                    'description' => "Beban Penyusutan: {$asset->name}",
                ],
                [
                    'account_id' => $accumDeprId,
                    'debit_amount' => 0,
                    'credit_amount' => $monthlyDepreciation,
                    'description' => "Akumulasi Penyusutan: {$asset->name}",
                ],
            ];

            $journalData = [
                'date' => $depreciationDate->format('Y-m-d'),
                'period_id' => $period->id,
                'description' => "Penyusutan Aset: {$asset->code} - {$asset->name}",
                'source' => 'fixed_asset',
                'type' => 'normal',
                'ref_type' => 'fixed_assets',
                'ref_id' => $asset->id,
                'created_by' => Auth::id(),
            ];

            $journal = JournalService::createJournal($journalData, $journalLines);

            $newAccumulated = (float) $asset->accumulated_depreciation + $monthlyDepreciation;
            $bookValue = (float) $asset->acquisition_cost - $newAccumulated;
            $isFully = $bookValue <= (float) $asset->salvage_value;

            $asset->update([
                'accumulated_depreciation' => $newAccumulated,
                'monthly_depreciation' => $monthlyDepreciation,
                'is_fully_depreciated' => $isFully,
                'status' => $isFully ? 'depreciated' : 'active',
            ]);

            return $journal;
        });
    }

    public static function calculateAllMonthlyDepreciation(string $date = null): array
    {
        $depreciationDate = $date ? Carbon::parse($date) : Carbon::now();
        $results = [];

        $assets = FixedAsset::active()->get();

        foreach ($assets as $asset) {
            try {
                $journal = self::recordDepreciation($asset, $depreciationDate->format('Y-m-d'));
                $results['success'][] = [
                    'asset_id' => $asset->id,
                    'code' => $asset->code,
                    'name' => $asset->name,
                    'amount' => $asset->monthly_depreciation,
                    'journal_id' => $journal->id,
                ];
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'asset_id' => $asset->id,
                    'code' => $asset->code,
                    'name' => $asset->name,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    public static function disposeAsset(FixedAsset $asset, string $date, ?float $disposalAmount = null): void
    {
        if ($asset->status === 'disposed') {
            throw new InvalidAccountException("Aset '{$asset->name}' sudah dihapus.");
        }

        $disposalDate = Carbon::parse($date);
        $bookValue = (float) $asset->acquisition_cost - (float) $asset->accumulated_depreciation;
        $proceeds = $disposalAmount ?? 0;
        $gainLoss = $proceeds - $bookValue;

        if (!$asset->asset_account_id) {
            throw new InvalidAccountException('Akun aset tetap tidak ditemukan.');
        }

        $accumDeprId = $asset->accumulated_depreciation_account_id
            ?? Account::where('code', '1700-00-021')->value('id');

        $period = Period::whereYear('start_date', $disposalDate->year)
            ->whereMonth('start_date', $disposalDate->month)
            ->first();

        if (!$period || $period->is_closed) {
            throw new InvalidAccountException('Periode tidak tersedia atau sudah ditutup.');
        }

        DB::transaction(function () use ($asset, $disposalDate, $bookValue, $proceeds, $gainLoss, $period, $accumDeprId) {
            $journalLines = [];

            // Kredit: hapus biaya perolehan aset
            $journalLines[] = [
                'account_id' => $asset->asset_account_id,
                'debit_amount' => 0,
                'credit_amount' => (float) $asset->acquisition_cost,
                'description' => "Penghapusan aset: {$asset->name}",
            ];

            // Debit: hapus akumulasi penyusutan dari akun kontra-aset
            if ($accumDeprId && (float) $asset->accumulated_depreciation > 0) {
                $journalLines[] = [
                    'account_id' => $accumDeprId,
                    'debit_amount' => (float) $asset->accumulated_depreciation,
                    'credit_amount' => 0,
                    'description' => "Penghapusan akumulasi penyusutan: {$asset->name}",
                ];
            }

            if ($proceeds > 0) {
                $cashAccountId = \App\Helpers\AccountResolver::resolve('cash_account_id', '1101');

                if ($cashAccountId) {
                    $journalLines[] = [
                        'account_id' => $cashAccountId,
                        'debit_amount' => $proceeds,
                        'credit_amount' => 0,
                        'description' => "Hasil penghapusan aset: {$asset->name}",
                    ];
                }
            }

            if ($gainLoss > 0) {
                $gainAccountId = \App\Helpers\AccountResolver::gainOnDisposal();
                if ($gainAccountId) {
                    $journalLines[] = [
                        'account_id' => $gainAccountId,
                        'debit_amount' => 0,
                        'credit_amount' => abs($gainLoss),
                        'description' => "Keuntungan penghapusan aset: {$asset->name}",
                    ];
                }
            } elseif ($gainLoss < 0) {
                $lossAccountId = \App\Helpers\AccountResolver::lossOnDisposal();
                if ($lossAccountId) {
                    $journalLines[] = [
                        'account_id' => $lossAccountId,
                        'debit_amount' => abs($gainLoss),
                        'credit_amount' => 0,
                        'description' => "Kerugian penghapusan aset: {$asset->name}",
                    ];
                }
            }

            $journalData = [
                'date' => $disposalDate->format('Y-m-d'),
                'period_id' => $period->id,
                'description' => "Penghapusan Aset: {$asset->code} - {$asset->name}",
                'source' => 'fixed_asset',
                'type' => 'normal',
                'ref_type' => 'fixed_assets',
                'ref_id' => $asset->id,
                'created_by' => Auth::id(),
            ];

            $journal = JournalService::createJournal($journalData, $journalLines);

            $asset->update([
                'status' => 'disposed',
                'disposed_date' => $disposalDate,
                'disposal_amount' => $proceeds,
            ]);
        });
    }

    public static function reverseDepreciation(FixedAsset $asset): void
    {
        if ($asset->status !== 'depreciated' && $asset->status !== 'active') {
            throw new InvalidAccountException("Aset '{$asset->name}' tidak dapat dibatalkan.");
        }

        $lastJournal = Journal::where('ref_type', 'fixed_assets')
            ->where('ref_id', $asset->id)
            ->where('type', 'normal')
            ->latest('date')
            ->first();

        if (!$lastJournal) {
            throw new InvalidAccountException('Tidak ada jurnal penyusutan untuk dibatalkan.');
        }

        DB::transaction(function () use ($asset, $lastJournal) {
            JournalService::voidJournal($lastJournal, "Pembatalan penyusutan aset {$asset->code}");

            $amount = 0;
            foreach ($lastJournal->lines as $line) {
                if ($line->credit_amount > 0) {
                    $amount = (float) $line->credit_amount;
                }
            }

            if ($amount > 0) {
                $asset->update([
                    'accumulated_depreciation' => max(0, (float) $asset->accumulated_depreciation - $amount),
                    'is_fully_depreciated' => false,
                    'status' => 'active',
                ]);
            }
        });
    }
}
