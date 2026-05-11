<?php

namespace App\Console\Commands;

use App\Services\FixedAssetService;
use Illuminate\Console\Command;

class RunMonthlyDepreciation extends Command
{
    protected $signature = 'depreciation:run {--date= : Date to run depreciation for (default: today)}';

    protected $description = 'Run monthly depreciation for all active fixed assets';

    public function handle(): int
    {
        $date = $this->option('date') ?: now()->format('Y-m-d');

        $this->info("Running monthly depreciation for: {$date}");

        $results = FixedAssetService::calculateAllMonthlyDepreciation($date);

        $success = count($results['success'] ?? []);
        $failed = count($results['failed'] ?? []);

        if ($success > 0) {
            $this->info("Successfully depreciated {$success} asset(s):");
            foreach ($results['success'] as $item) {
                $this->line("  ✓ {$item['code']} - {$item['name']}: Rp " . number_format($item['amount'], 0, ',', '.'));
            }
        }

        if ($failed > 0) {
            $this->error("Failed to depreciate {$failed} asset(s):");
            foreach ($results['failed'] as $item) {
                $this->error("  ✗ {$item['code']} - {$item['name']}: {$item['error']}");
            }
        }

        if ($success === 0 && $failed === 0) {
            $this->warn('No active assets found for depreciation.');
        }

        $this->info('Depreciation run completed.');

        return Command::SUCCESS;
    }
}
