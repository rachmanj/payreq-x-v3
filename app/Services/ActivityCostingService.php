<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\RealizationDetail;
use App\Models\VerificationJournalDetail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

class ActivityCostingService
{
    public const EFFECTIVE_ACTIVITY_SQL = 'CASE WHEN realization_details.activity_excluded = 1 THEN NULL WHEN realization_details.activity_id IS NOT NULL THEN realization_details.activity_id ELSE realizations.activity_id END';

    /**
     * @param  array<string, mixed>  $filters
     */
    public function indexQuery(array $filters): Builder
    {
        $costSub = $this->costAggregationSubquery($filters);

        return Activity::query()
            ->with(['account', 'department'])
            ->select('activities.*')
            ->selectRaw('COALESCE(cost_agg.realization_count, 0) as realization_count')
            ->selectRaw('COALESCE(cost_agg.note_count, 0) as note_count')
            ->selectRaw('COALESCE(cost_agg.total_cost, 0) as total_cost')
            ->leftJoinSub($costSub, 'cost_agg', 'cost_agg.effective_activity_id', '=', 'activities.id')
            ->when($filters['project'] ?? null, fn (Builder $q, $value) => $q->where('activities.project', $value))
            ->when($filters['department_id'] ?? null, fn (Builder $q, $value) => $q->where('activities.department_id', $value))
            ->when($filters['periode'] ?? null, fn (Builder $q, $value) => $q->where('activities.periode', $value))
            ->when($filters['status'] ?? null, fn (Builder $q, $value) => $q->where('activities.status', $value))
            ->when($filters['activity_id'] ?? null, fn (Builder $q, $value) => $q->where('activities.id', $value))
            ->orderByDesc('activities.id');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function notaQuery(int $activityId, array $filters): Builder
    {
        return RealizationDetail::query()
            ->with([
                'account',
                'department',
                'realization.verificationJournal',
            ])
            ->where('realization_details.activity_excluded', false)
            ->where(function (Builder $inner) use ($activityId) {
                $inner->where('realization_details.activity_id', $activityId)
                    ->orWhere(function (Builder $nested) use ($activityId) {
                        $nested->whereNull('realization_details.activity_id')
                            ->whereHas('realization', fn (Builder $realization) => $realization->where('activity_id', $activityId));
                    });
            })
            ->when($filters['date_from'] ?? null, fn (Builder $q, $value) => $q->whereDate('realization_details.expense_date', '>=', $value))
            ->when($filters['date_to'] ?? null, fn (Builder $q, $value) => $q->whereDate('realization_details.expense_date', '<=', $value))
            ->orderByDesc('realization_details.expense_date')
            ->orderByDesc('realization_details.id');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function journalLinesQuery(int $activityId, array $filters): Builder
    {
        return VerificationJournalDetail::query()
            ->with(['verificationJournal'])
            ->where('activity_id', $activityId)
            ->when($filters['date_from'] ?? null, fn (Builder $q, $value) => $q->whereDate('realization_date', '>=', $value))
            ->when($filters['date_to'] ?? null, fn (Builder $q, $value) => $q->whereDate('realization_date', '<=', $value))
            ->orderByDesc('realization_date')
            ->orderByDesc('id');
    }

    public function isReclassifiedForActivity(Activity $activity, RealizationDetail $detail): bool
    {
        if (! $activity->isReklasifikasi()) {
            return false;
        }

        return app(VerificationJournalAggregator::class)->isExpenseAccount($detail->account);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filtersFromRequest(array $input): array
    {
        return [
            'date_from' => $input['date_from'] ?? null,
            'date_to' => $input['date_to'] ?? null,
            'project' => $input['project'] ?? null,
            'department_id' => $input['department_id'] ?? null,
            'activity_id' => $input['activity_id'] ?? null,
            'periode' => $input['periode'] ?? null,
            'status' => $input['status'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function costAggregationSubquery(array $filters): QueryBuilder
    {
        return DB::table('realization_details')
            ->join('realizations', 'realizations.id', '=', 'realization_details.realization_id')
            ->selectRaw(self::EFFECTIVE_ACTIVITY_SQL.' as effective_activity_id')
            ->selectRaw('COUNT(DISTINCT realization_details.realization_id) as realization_count')
            ->selectRaw('COUNT(*) as note_count')
            ->selectRaw('SUM(realization_details.amount) as total_cost')
            ->when($filters['date_from'] ?? null, fn (QueryBuilder $q, $value) => $q->whereDate('realization_details.expense_date', '>=', $value))
            ->when($filters['date_to'] ?? null, fn (QueryBuilder $q, $value) => $q->whereDate('realization_details.expense_date', '<=', $value))
            ->groupBy('effective_activity_id')
            ->havingNotNull('effective_activity_id');
    }
}
