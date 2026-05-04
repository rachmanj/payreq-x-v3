<?php

namespace App\Console\Commands;

use App\Models\Anggaran;
use App\Services\AnggaranReleaseService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AnggaranInactivateManyCommand extends Command
{
    protected $signature = 'anggaran:inactivate-many
                            {--last-month : Only rows whose document date falls in the full calendar month before the current month}
                            {--project= : Limit to anggarans.project (creator project code)}
                            {--dry-run : List matches without updating}';

    protected $description = 'Bulk inactivate approved RAB rows (Reports parity); skips type=buc';

    public function handle(AnggaranReleaseService $releaseService): int
    {
        if (! $this->option('last-month')) {
            $this->error('Specify --last-month (only supported selector for this command).');

            return self::FAILURE;
        }

        $timezone = (string) config('app.timezone');
        $now = Carbon::now($timezone);
        $monthStart = $now->copy()->subMonth()->startOfMonth()->startOfDay();
        $monthEnd = $now->copy()->subMonth()->endOfMonth()->endOfDay();

        $project = $this->option('project');

        $count = $this->matchingQuery($monthStart, $monthEnd, $project)->count();

        $this->info(sprintf(
            'Window: %s → %s (%s)%s',
            $monthStart->toDateString(),
            $monthEnd->toDateString(),
            $timezone,
            $project ? "; project filter: {$project}" : '; all projects'
        ));
        $this->info('Excluding type=buc (BUC RABs are never inactivated by this command).');
        $this->info("Matching approved active rows: {$count}");

        if ($count === 0) {
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $ids = $this->matchingQuery($monthStart, $monthEnd, $project)->orderBy('id')->limit(50)->pluck('id');
            $this->line('Sample IDs (max 50): '.$ids->implode(', '));

            return self::SUCCESS;
        }

        DB::beginTransaction();
        try {
            $this->matchingQuery($monthStart, $monthEnd, $project)->chunkById(100, function ($rows) use ($releaseService): void {
                $ids = $rows->pluck('id')->map(fn ($id) => (int) $id)->all();
                Anggaran::whereIn('id', $ids)->update(['is_active' => 0]);

                foreach ($ids as $id) {
                    $releaseService->forgetDetailCaches($id);
                }
            });

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $releaseService->flushListingCaches();

        $this->info("Inactivated {$count} row(s).");

        return self::SUCCESS;
    }

    protected function matchingQuery(Carbon $monthStart, Carbon $monthEnd, ?string $project): Builder
    {
        return Anggaran::query()
            ->where('is_active', 1)
            ->where('status', 'approved')
            ->where('type', '!=', 'buc')
            ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->when($project !== null && $project !== '', fn ($q) => $q->where('project', $project));
    }
}
