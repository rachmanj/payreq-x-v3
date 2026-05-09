<?php

namespace App\Console\Commands;

use App\Models\Anggaran;
use App\Services\AnggaranReleaseService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ExpirePeriodicAnggarans extends Command
{
    protected $signature = 'anggaran:expire-periodic';

    protected $description = 'Deactivate periodic (periode) budgets whose period has ended';

    public function __construct(
        protected AnggaranReleaseService $releaseService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $today = Carbon::today();
        $count = 0;

        Anggaran::query()
            ->where('type', 'periode')
            ->where('is_active', 1)
            ->where('status', 'approved')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($today, &$count): void {
                foreach ($rows as $anggaran) {
                    $end = $this->resolvePeriodicEndDate($anggaran);
                    if ($end === null) {
                        continue;
                    }
                    if ($end->lt($today)) {
                        $anggaran->update(['is_active' => 0]);
                        $this->releaseService->forgetDetailCaches((int) $anggaran->id);
                        $count++;
                    }
                }
            });

        $this->releaseService->flushListingCaches();

        $this->info("Deactivated {$count} periodic anggaran record(s).");

        return self::SUCCESS;
    }

    protected function resolvePeriodicEndDate(Anggaran $anggaran): ?Carbon
    {
        if ($anggaran->end_date) {
            return Carbon::parse($anggaran->end_date)->startOfDay();
        }

        if ($anggaran->periode_anggaran) {
            return Carbon::parse($anggaran->periode_anggaran)->endOfMonth()->startOfDay();
        }

        return null;
    }
}
