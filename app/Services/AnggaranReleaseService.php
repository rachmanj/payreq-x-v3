<?php

namespace App\Services;

use App\Models\Anggaran;
use App\Models\Payreq;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnggaranReleaseService
{
    public function effectiveRabIdForPayqueries(Anggaran $anggaran): int
    {
        return $anggaran->old_rab_id !== null ? (int) $anggaran->old_rab_id : (int) $anggaran->id;
    }

    /**
     * @return Collection<int, Payreq>
     */
    public function payreqsWithOutgoings(Anggaran $anggaran): Collection
    {
        $rabId = $this->effectiveRabIdForPayqueries($anggaran);

        return Payreq::query()
            ->where('rab_id', $rabId)
            ->whereHas('outgoings')
            ->with([
                'realization.realizationDetails',
                'outgoings',
                'requestor',
            ])
            ->orderByDesc('created_at')
            ->get();
    }

    public function calculateTotalRelease(Anggaran $anggaran): float
    {
        $payreqs = $this->payreqsWithOutgoings($anggaran);
        $totalRelease = 0.0;

        foreach ($payreqs as $payreq) {
            if ($payreq->realization && $payreq->realization->realizationDetails->count() > 0) {
                $totalRelease += (float) $payreq->realization->realizationDetails->sum('amount');
            } else {
                $totalRelease += (float) $payreq->outgoings->sum('amount');
            }
        }

        return $totalRelease;
    }

    /**
     * @return array{amount: float|int|string, persen: float|int|string, payreqs: array<int, Payreq>, payreq_count: int}
     */
    public function progressSummary(Anggaran $anggaran): array
    {
        $payreqs = $this->payreqsWithOutgoings($anggaran);

        $payreqList = [];
        foreach ($payreqs as $payreq) {
            if ($payreq->type === 'advance') {
                if ($payreq->realization && $payreq->realization->realizationDetails->count() > 0) {
                    $payreq->nomor = $payreq->realization->nomor;
                    $payreq->amount = $payreq->realization->realizationDetails->sum('amount');
                    $payreqList[] = $payreq;
                } else {
                    $payreq->amount = $payreq->outgoings->sum('amount');
                    $payreqList[] = $payreq;
                }
            } else {
                $payreq->amount = $payreq->outgoings->sum('amount');
                $payreqList[] = $payreq;
            }
        }

        $totalRelease = 0.0;
        foreach ($payreqs as $payreq) {
            if ($payreq->realization && $payreq->realization->realizationDetails->count() > 0) {
                $totalRelease += (float) $payreq->realization->realizationDetails->sum('amount');
            } else {
                $totalRelease += (float) $payreq->outgoings->sum('amount');
            }
        }

        $budgetAmount = (float) $anggaran->amount;

        return [
            'amount' => $totalRelease,
            'persen' => $totalRelease > 0 && $budgetAmount > 0
                ? number_format(($totalRelease / $budgetAmount) * 100, 2)
                : 0,
            'payreqs' => $payreqList,
            'payreq_count' => $payreqs->count(),
        ];
    }

    public function formatPercent(float $totalRelease, float $budgetAmount): string
    {
        return $totalRelease > 0 && $budgetAmount > 0
            ? number_format(($totalRelease / $budgetAmount) * 100, 2)
            : '0';
    }

    public function parsePersenToFloat(Anggaran $anggaran): float
    {
        $raw = $anggaran->persen;

        if ($raw === null || $raw === '') {
            return 0.0;
        }

        return (float) str_replace(',', '', (string) $raw);
    }

    public function isOverThreshold(Anggaran $anggaran): bool
    {
        $threshold = (int) ($anggaran->warning_threshold ?? 80);

        return $this->parsePersenToFloat($anggaran) >= $threshold;
    }

    public function isExceeded(Anggaran $anggaran): bool
    {
        return $this->parsePersenToFloat($anggaran) > 100.0;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function aggregateDashboardStats(User $user, array $userRoles, array $filters = []): array
    {
        $base = $this->dashboardBaseQuery($user, $userRoles);

        if (! empty($filters['project'])) {
            $base->where('rab_project', $filters['project']);
        }
        if (! empty($filters['type'])) {
            $base->where('type', $filters['type']);
        }
        if (! empty($filters['fund_status'])) {
            $base->where('fund_status', $filters['fund_status']);
        }

        $countVisible = (clone $base)->count();

        $approvedSubset = (clone $base)->where('status', 'approved');
        $countApproved = (clone $approvedSubset)->count();
        $sumBudgetApproved = (float) (clone $approvedSubset)->sum('amount');
        $sumBalanceApproved = (float) (clone $approvedSubset)->sum('balance');
        $remaining = $sumBudgetApproved - $sumBalanceApproved;

        $avgUtilization = $sumBudgetApproved > 0
            ? round(($sumBalanceApproved / $sumBudgetApproved) * 100, 2)
            : 0.0;

        $approvedRows = (clone $approvedSubset)->get(['id', 'persen', 'warning_threshold', 'fund_status']);

        $nearThreshold = 0;
        $exceeded = 0;
        foreach ($approvedRows as $row) {
            if ($this->isExceeded($row)) {
                $exceeded++;
            } elseif ($this->isOverThreshold($row)) {
                $nearThreshold++;
            }
        }

        $pendingFundPool = (clone $approvedSubset)->where('fund_status', Anggaran::FUND_STATUS_PENDING)->count();

        $byType = (clone $approvedSubset)
            ->selectRaw('type, SUM(amount) as total_amount, SUM(balance) as total_balance, COUNT(*) as cnt')
            ->groupBy('type')
            ->get()
            ->map(function ($row) {
                $budget = (float) $row->total_amount;
                $released = (float) $row->total_balance;

                return [
                    'type' => $row->type,
                    'count' => (int) $row->cnt,
                    'total_amount' => $budget,
                    'total_balance' => $released,
                    'utilization' => $budget > 0 ? round(($released / $budget) * 100, 2) : 0.0,
                ];
            });

        $byProject = (clone $approvedSubset)
            ->selectRaw('rab_project, SUM(amount) as total_amount, SUM(balance) as total_balance, COUNT(*) as cnt')
            ->groupBy('rab_project')
            ->orderBy('rab_project')
            ->get()
            ->map(function ($row) {
                $budget = (float) $row->total_amount;
                $released = (float) $row->total_balance;

                return [
                    'project' => $row->rab_project,
                    'count' => (int) $row->cnt,
                    'total_amount' => $budget,
                    'total_balance' => $released,
                    'utilization' => $budget > 0 ? round(($released / $budget) * 100, 2) : 0.0,
                ];
            });

        $expiringSoon = (clone $approvedSubset)
            ->where('type', 'periode')
            ->where('is_active', 1)
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [now()->toDateString(), now()->addDays(30)->toDateString()])
            ->orderBy('end_date')
            ->limit(50)
            ->get();

        $exceededBudgets = (clone $approvedSubset)
            ->get()
            ->filter(fn (Anggaran $a) => $this->isExceeded($a))
            ->sortByDesc(function (Anggaran $a) {
                $spent = (float) $a->balance;
                $budget = (float) $a->amount;

                return $spent - $budget;
            })
            ->values()
            ->take(20);

        return [
            'count_visible' => $countVisible,
            'count_approved' => $countApproved,
            'sum_budget_approved' => $sumBudgetApproved,
            'sum_balance_approved' => $sumBalanceApproved,
            'sum_remaining_approved' => $remaining,
            'avg_utilization' => $avgUtilization,
            'count_near_threshold' => $nearThreshold,
            'count_exceeded' => $exceeded,
            'count_pending_fund_pool' => $pendingFundPool,
            'by_type' => $byType,
            'by_project' => $byProject,
            'expiring_soon' => $expiringSoon,
            'exceeded_budgets' => $exceededBudgets,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function aggregateByDepartment(string $projectCode): array
    {
        $rows = Anggaran::query()
            ->where('status', 'approved')
            ->where('is_active', 1)
            ->where('rab_project', $projectCode)
            ->selectRaw('department_id, SUM(amount) as total_amount, SUM(balance) as total_balance, COUNT(*) as cnt')
            ->groupBy('department_id')
            ->get();

        $departmentIds = $rows->pluck('department_id')->filter()->unique()->all();
        $names = DB::table('departments')
            ->whereIn('id', $departmentIds)
            ->pluck('department_name', 'id');

        $out = [];
        foreach ($rows as $row) {
            $budget = (float) $row->total_amount;
            $released = (float) $row->total_balance;
            $out[] = [
                'department_id' => (int) $row->department_id,
                'department_name' => $names[(int) $row->department_id] ?? '—',
                'count' => (int) $row->cnt,
                'total_amount' => $budget,
                'total_balance' => $released,
                'remaining' => $budget - $released,
                'avg_utilization' => $budget > 0 ? round(($released / $budget) * 100, 2) : 0.0,
            ];
        }

        usort($out, fn ($a, $b) => strcmp((string) $a['department_name'], (string) $b['department_name']));

        return $out;
    }

    /**
     * @param  array<int, string>  $userRoles
     */
    public function dashboardBaseQuery(User $user, array $userRoles): Builder
    {
        $query = Anggaran::query()->where('is_active', 1);

        if (! array_intersect(['superadmin', 'admin'], $userRoles)) {
            $query->where('project', $user->project);
        }

        if (array_intersect(['superadmin', 'admin'], $userRoles)) {
            $query->whereIn('status', ['approved']);
        }

        return $query;
    }

    public function syncStoredTotals(Anggaran $anggaran): void
    {
        $totalRelease = $this->calculateTotalRelease($anggaran);
        $budgetAmount = (float) $anggaran->amount;
        $persen = $this->formatPercent($totalRelease, $budgetAmount);

        $anggaran->update([
            'balance' => $totalRelease,
            'persen' => $persen,
        ]);
    }

    public function syncAllApprovedStoredTotals(): void
    {
        DB::beginTransaction();
        try {
            Anggaran::query()->where('status', 'approved')->orderBy('id')->chunk(100, function ($anggarans): void {
                foreach ($anggarans as $anggaran) {
                    $this->syncStoredTotals($anggaran);
                }
            });
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function forgetDetailCaches(int $anggaranId): void
    {
        Cache::forget('anggaran_'.$anggaranId);
        Cache::forget('anggaran_show_'.$anggaranId);
        Cache::forget('anggaran_release_'.$anggaranId);
    }

    public function flushListingCaches(): void
    {
        if (Cache::getStore() instanceof \Illuminate\Cache\TaggableStore) {
            Cache::tags(['anggarans'])->flush();
        } else {
            $projects = Project::query()->pluck('code')->toArray();
            foreach ($projects as $project) {
                foreach (['active', 'inactive'] as $status) {
                    Cache::forget('anggarans_data_'.$status.'_'.$project);
                }
            }
        }
    }

    public function flushAllReportingCaches(): void
    {
        $this->flushListingCaches();

        foreach (Project::query()->pluck('code')->toArray() as $project) {
            Cache::forget('periode_anggarans_'.$project);
            Cache::forget('periode_ofrs_'.$project);
        }

        Cache::forget('projects_all');

        foreach (Anggaran::query()->pluck('id')->toArray() as $id) {
            $this->forgetDetailCaches((int) $id);
        }
    }
}
