<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Activity;
use App\Models\ApprovalPlan;
use App\Models\Realization;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

trait HandlesApprovalActivityDetails
{
    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Activity>
     */
    protected function openActivitiesForProject(?string $project)
    {
        return Activity::query()
            ->open()
            ->where(function ($query) use ($project) {
                $query->whereNull('project');
                if ($project) {
                    $query->orWhere('project', $project);
                }
            })
            ->orderBy('code')
            ->get();
    }

    protected function requestContainsActivityFields(Request $request): bool
    {
        if ($request->has('activity_id')) {
            return true;
        }

        foreach ($request->input('details', []) as $detail) {
            if (! is_array($detail)) {
                continue;
            }

            if (array_key_exists('activity_id', $detail) || array_key_exists('activity_excluded', $detail)) {
                return true;
            }
        }

        return false;
    }

    protected function assertActivityChangesAllowed(ApprovalPlan $document, Request $request): void
    {
        if ((int) $document->status === 0) {
            return;
        }

        if (! $this->requestContainsActivityFields($request)) {
            return;
        }

        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Perubahan kegiatan tidak diizinkan karena dokumen sudah disetujui.',
        ], 403));
    }

    /**
     * @param  array<string, mixed>  $detailData
     * @return array<string, mixed>
     */
    protected function activityFieldsForDetailUpdate(array $detailData, bool $canUpdateActivity): array
    {
        if (! $canUpdateActivity) {
            return [];
        }

        $fields = [];

        if (array_key_exists('activity_id', $detailData)) {
            $fields['activity_id'] = filled($detailData['activity_id'])
                ? (int) $detailData['activity_id']
                : null;
        }

        if (array_key_exists('activity_excluded', $detailData)) {
            $fields['activity_excluded'] = filter_var(
                $detailData['activity_excluded'],
                FILTER_VALIDATE_BOOLEAN
            );
        }

        return $fields;
    }

    protected function validateActivityFields(Request $request): void
    {
        $rules = [
            'activity_id' => ['nullable', 'integer', 'exists:activities,id'],
            'details.*.activity_id' => ['nullable', 'integer', 'exists:activities,id'],
            'details.*.activity_excluded' => ['nullable', 'boolean'],
        ];

        $request->validate($rules);

        $activityIds = collect($request->input('details', []))
            ->pluck('activity_id')
            ->filter()
            ->push($request->input('activity_id'))
            ->filter()
            ->unique()
            ->values();

        foreach ($activityIds as $activityId) {
            $activity = Activity::query()->open()->find($activityId);
            if (! $activity) {
                throw ValidationException::withMessages([
                    'activity_id' => 'Kegiatan tidak tersedia atau sudah ditutup.',
                ]);
            }
        }
    }

    protected function updateRealizationHeaderActivity(Realization $realization, Request $request, bool $canUpdateActivity): void
    {
        if (! $canUpdateActivity || ! $request->has('activity_id')) {
            return;
        }

        $realization->update([
            'activity_id' => filled($request->input('activity_id'))
                ? (int) $request->input('activity_id')
                : null,
        ]);
    }

    protected function canUpdateActivityOnPlan(ApprovalPlan $document): bool
    {
        return (int) $document->status === 0;
    }
}
