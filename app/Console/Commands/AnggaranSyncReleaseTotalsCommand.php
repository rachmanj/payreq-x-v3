<?php

namespace App\Console\Commands;

use App\Services\AnggaranReleaseService;
use Illuminate\Console\Command;

class AnggaranSyncReleaseTotalsCommand extends Command
{
    protected $signature = 'anggaran:sync-release-totals';

    protected $description = 'Recalculate stored balance and utilization percent for all approved RAB rows';

    public function handle(AnggaranReleaseService $releaseService): int
    {
        try {
            $releaseService->syncAllApprovedStoredTotals();
            $releaseService->flushListingCaches();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Anggaran release totals synchronized.');

        return self::SUCCESS;
    }
}
