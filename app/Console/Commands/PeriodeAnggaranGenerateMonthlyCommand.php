<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\PeriodeAnggaranGeneratorService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PeriodeAnggaranGenerateMonthlyCommand extends Command
{
    protected $signature = 'periode-anggaran:generate-monthly
                            {--projects=* : Project codes; default = active+selectable projects}
                            {--types=anggaran,ofr : Comma-separated periode types}
                            {--dry-run : Preview changes without persisting}';

    protected $description = 'Bulk-create monthly Periode Anggaran and OFR rows for all (or selected) projects';

    public function handle(PeriodeAnggaranGeneratorService $generator): int
    {
        $timezone = (string) config('app.timezone');
        $periode = Carbon::now($timezone)->startOfMonth();

        $projectCodes = $this->resolveProjectCodes();
        if ($projectCodes === []) {
            $this->error('No projects matched the selection.');

            return self::FAILURE;
        }

        $types = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) $this->option('types'))
        )));

        $invalidTypes = array_diff($types, ['anggaran', 'ofr']);
        if ($types === [] || $invalidTypes !== []) {
            $this->error('Invalid --types. Allowed values: anggaran, ofr');

            return self::FAILURE;
        }

        $description = sprintf('Auto-generated on %s', Carbon::now($timezone)->toDateTimeString());
        $dryRun = (bool) $this->option('dry-run');

        $this->info(sprintf(
            'Periode: %s (%s); projects: %s; types: %s%s',
            $periode->format('F Y'),
            $timezone,
            implode(', ', $projectCodes),
            implode(', ', $types),
            $dryRun ? '; DRY RUN' : ''
        ));

        $result = $generator->generate(
            $periode,
            $types,
            $projectCodes,
            true,
            $description,
            true,
            $dryRun,
        );

        $this->info(sprintf(
            'Created: %d; skipped (already exist): %d; previous periods deactivated: %d',
            $result['created'],
            $result['skipped'],
            $result['deactivated'],
        ));

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function resolveProjectCodes(): array
    {
        $requested = $this->option('projects');

        if ($requested === [] || $requested === null) {
            return Project::active()->selectable()->orderBy('code')->pluck('code')->all();
        }

        return array_values(array_unique(array_map('strval', $requested)));
    }
}
