<?php

namespace App\Services;

use App\Models\Bapsb;
use App\Models\User;
use Carbon\Carbon;

class BapsbComplianceService
{
    public function __construct(
        protected BapsbService $bapsbService
    ) {}

    public function submissionDeadlineForPeriod(string $period): Carbon
    {
        return Carbon::createFromFormat('Y-m', $period)
            ->addMonth()
            ->day(5)
            ->endOfDay();
    }

    public function isPeriodSubmissionLate(string $project, string $period, ?Carbon $asOf = null): bool
    {
        if (! $this->bapsbService->projectHasNeedsBilyetGiro($project)) {
            return false;
        }

        $asOf = $asOf ?? Carbon::now();
        $deadline = $this->submissionDeadlineForPeriod($period);

        if ($asOf->lte($deadline)) {
            return false;
        }

        return ! Bapsb::query()
            ->where('project', $project)
            ->where('period', $period)
            ->whereNotNull('submitted_at')
            ->exists();
    }

    /**
     * @return array<int, array{project: string, period: string, deadline: string}>
     */
    public function outstandingSubmissions(?Carbon $asOf = null): array
    {
        $asOf = $asOf ?? Carbon::now();
        $projects = $this->bapsbService->projectsRequiringBapsb();
        $results = [];

        $cursor = $asOf->copy()->startOfMonth()->subMonths(12);
        $currentPeriod = $asOf->copy()->startOfMonth();

        while ($cursor->lte($currentPeriod)) {
            $period = $cursor->format('Y-m');
            foreach ($projects as $project) {
                if ($this->isPeriodSubmissionLate($project, $period, $asOf)) {
                    $results[] = [
                        'project' => $project,
                        'period' => $period,
                        'deadline' => $this->submissionDeadlineForPeriod($period)->toDateString(),
                    ];
                }
            }
            $cursor->addMonth();
        }

        return $results;
    }

    public function getWarningForUser(?User $user): ?array
    {
        if (! $user || ! $user->project) {
            return null;
        }

        if (! $user->can('akses_bapsb')) {
            return null;
        }

        if (! $this->bapsbService->projectHasNeedsBilyetGiro($user->project)) {
            return null;
        }

        $previousMonth = Carbon::now()->subMonth()->format('Y-m');
        if (! $this->isPeriodSubmissionLate($user->project, $previousMonth)) {
            return null;
        }

        $deadline = $this->submissionDeadlineForPeriod($previousMonth);

        return [
            'variant' => 'warning',
            'title' => 'BAPSB submission overdue',
            'message' => 'BAPSB for period '.$previousMonth.' was due by '.$deadline->format('d M Y').'. Please prepare, upload, and submit the signed report.',
            'period' => $previousMonth,
            'deadline' => $deadline->toDateString(),
        ];
    }

    public function pendingValidationCount(): int
    {
        return Bapsb::query()
            ->whereNotNull('submitted_at')
            ->where('validation_status', Bapsb::VALIDATION_PENDING)
            ->count();
    }
}
