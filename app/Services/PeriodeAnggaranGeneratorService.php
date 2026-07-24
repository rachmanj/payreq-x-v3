<?php

namespace App\Services;

use App\Models\PeriodeAnggaran;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PeriodeAnggaranGeneratorService
{
    /**
     * @param  array<int, string>  $types
     * @param  array<int, string>  $projectCodes
     * @return array{created: int, skipped: int, deactivated: int}
     */
    public function generate(
        Carbon $periode,
        array $types,
        array $projectCodes,
        bool $isActive,
        ?string $description,
        bool $deactivatePrevious,
        bool $dryRun = false,
    ): array {
        $periodeDate = $periode->copy()->startOfMonth()->toDateString();
        $created = 0;
        $skipped = 0;
        $deactivated = 0;

        $run = function () use (
            $types,
            $projectCodes,
            $isActive,
            $description,
            $deactivatePrevious,
            $dryRun,
            $periodeDate,
            &$created,
            &$skipped,
            &$deactivated
        ): void {
            foreach ($projectCodes as $projectCode) {
                foreach ($types as $type) {
                    $exists = PeriodeAnggaran::query()
                        ->where('project', $projectCode)
                        ->where('periode_type', $type)
                        ->whereDate('periode', $periodeDate)
                        ->exists();

                    if ($exists) {
                        $skipped++;

                        continue;
                    }

                    if ($deactivatePrevious && $isActive) {
                        $deactivateQuery = PeriodeAnggaran::query()
                            ->where('project', $projectCode)
                            ->where('periode_type', $type)
                            ->where('is_active', 1)
                            ->whereDate('periode', '!=', $periodeDate);

                        if ($dryRun) {
                            $deactivated += $deactivateQuery->count();
                        } else {
                            $deactivated += $deactivateQuery->update(['is_active' => 0]);
                        }
                    }

                    if (! $dryRun) {
                        PeriodeAnggaran::create([
                            'periode' => $periodeDate,
                            'project' => $projectCode,
                            'periode_type' => $type,
                            'is_active' => $isActive ? 1 : 0,
                            'description' => $description,
                        ]);
                    }

                    $created++;
                }
            }
        };

        if ($dryRun) {
            $run();
        } else {
            DB::transaction($run);
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
            'deactivated' => $deactivated,
        ];
    }
}
