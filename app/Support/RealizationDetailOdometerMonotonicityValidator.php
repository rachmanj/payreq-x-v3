<?php

namespace App\Support;

use App\Models\RealizationDetail;
use Carbon\Carbon;
use Illuminate\Validation\Validator;

class RealizationDetailOdometerMonotonicityValidator
{
    /**
     * Validate the NEW reading against the unit's existing timeline, without
     * re-flagging pre-existing inconsistencies elsewhere in the history.
     *
     * A candidate (date D, HM H) must satisfy:
     *   - H >= max(HM) of all existing rows with date < D
     *   - H <= min(HM) of all existing rows with date > D
     * Same-day rows do not constrain the candidate (within-day order is free).
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

        $maxBefore = null;
        $minAfter = null;

        foreach ($rows as $row) {
            $day = self::dayOfRow($row);
            if ($day === null) {
                continue;
            }

            $hm = (int) $row->km_position;

            if ($day < $candidateDay) {
                $maxBefore = $maxBefore === null ? $hm : max($maxBefore, $hm);
            } elseif ($day > $candidateDay) {
                $minAfter = $minAfter === null ? $hm : min($minAfter, $hm);
            }
        }

        if ($maxBefore !== null && $candidateHm < $maxBefore) {
            $validator->errors()->add(
                'km_position',
                'HM reading is lower than a previously recorded reading for this unit (highest earlier reading: '.number_format($maxBefore).'). Adjust the HM or expense date.'
            );
        }

        if ($minAfter !== null && $candidateHm > $minAfter) {
            $validator->errors()->add(
                'km_position',
                'HM reading is higher than a later recorded reading for this unit (lowest later reading: '.number_format($minAfter).'). Adjust the HM or expense date.'
            );
        }
    }

    /**
     * @param  RealizationDetail  $row
     */
    private static function dayOfRow($row): ?string
    {
        $rawDay = $row->getRawOriginal('expense_date');
        if ($rawDay !== null && $rawDay !== '') {
            return substr((string) $rawDay, 0, 10);
        }

        if ($row->expense_date instanceof Carbon) {
            return $row->expense_date->timezone(config('app.timezone'))->toDateString();
        }

        if ($row->expense_date !== null) {
            return Carbon::parse($row->expense_date)->timezone(config('app.timezone'))->toDateString();
        }

        return null;
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
