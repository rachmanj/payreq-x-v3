<?php

namespace App\Support;

use App\Models\RealizationDetail;
use Carbon\Carbon;
use Illuminate\Validation\Validator;

class RealizationDetailOdometerMonotonicityValidator
{
    /**
     * Enforce non-decreasing HM across calendar days for a unit: HM_max(D) <= HM_min(D_next).
     * Rows without expense_date or km_position are excluded from the timeline.
     *
     * @param  array<string, mixed>  $input  Request input (must contain unit_no, km_position, expense_date when invoked)
     */
    public static function validate(
        Validator $validator,
        array $input,
        ?int $excludeDetailId = null
    ): void {
        $unitNo = $input['unit_no'] ?? null;
        $kmRaw = $input['km_position'] ?? null;
        $expenseRaw = $input['expense_date'] ?? null;

        if ($unitNo === null || $unitNo === '') {
            return;
        }

        if ($kmRaw === null || $kmRaw === '' || $expenseRaw === null || $expenseRaw === '') {
            return;
        }

        $candidateRaw = trim((string) $expenseRaw);
        $candidateDay = preg_match('/^\d{4}-\d{2}-\d{2}$/', $candidateRaw)
            ? $candidateRaw
            : Carbon::parse($candidateRaw)->timezone(config('app.timezone'))->toDateString();
        $candidateHm = (int) $kmRaw;

        $rows = RealizationDetail::query()
            ->where('unit_no', $unitNo)
            ->whereNotNull('expense_date')
            ->whereNotNull('km_position')
            ->when($excludeDetailId !== null, fn ($q) => $q->where('id', '!=', $excludeDetailId))
            ->get(['expense_date', 'km_position']);

        /** @var array<string, array<int>> $buckets date string => HM values */
        $buckets = [];

        foreach ($rows as $row) {
            $rawDay = $row->getRawOriginal('expense_date');
            if ($rawDay !== null && $rawDay !== '') {
                $day = substr((string) $rawDay, 0, 10);
            } elseif ($row->expense_date instanceof Carbon) {
                $day = $row->expense_date->timezone(config('app.timezone'))->toDateString();
            } else {
                $day = Carbon::parse($row->expense_date)->timezone(config('app.timezone'))->toDateString();
            }
            $buckets[$day][] = (int) $row->km_position;
        }

        if (! isset($buckets[$candidateDay])) {
            $buckets[$candidateDay] = [];
        }
        $buckets[$candidateDay][] = $candidateHm;

        ksort($buckets);

        if (self::breaksCrossDayMonotonicity($buckets)) {
            $validator->errors()->add(
                'km_position',
                'HM reading is inconsistent with expense dates for this unit: the odometer cannot decrease when moving forward in time. Adjust HM or expense date so earlier days do not show higher HM than later days.'
            );
        }
    }

    /**
     * @param  array<string, array<int>>  $bucketsByDateYmd  Calendar-day keys (YYYY-MM-DD) mapped to HM readings that day.
     */
    public static function breaksCrossDayMonotonicity(array $bucketsByDateYmd): bool
    {
        if ($bucketsByDateYmd === []) {
            return false;
        }

        ksort($bucketsByDateYmd);
        $days = array_keys($bucketsByDateYmd);

        for ($i = 0; $i < count($days) - 1; $i++) {
            $maxPrev = max($bucketsByDateYmd[$days[$i]]);
            $minNext = min($bucketsByDateYmd[$days[$i + 1]]);
            if ($maxPrev > $minNext) {
                return true;
            }
        }

        return false;
    }
}
