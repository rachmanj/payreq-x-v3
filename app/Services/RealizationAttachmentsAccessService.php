<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Realization;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class RealizationAttachmentsAccessService
{
    public function applyScopeToRealizationsQuery(Builder $query, User $user): void
    {
        if ($user->project === '000H') {
            return;
        }

        if ($user->can('realization_attachments_scope_bo')) {
            $query->whereNotIn('realizations.project', ['000H', 'APS']);

            return;
        }

        $query->where('realizations.project', $user->project);
    }

    /**
     * @return array<int, string>
     */
    public function allowedProjectCodesForFilters(User $user): array
    {
        if ($user->project === '000H') {
            return Project::active()->selectable()->orderBy('code')->pluck('code')->all();
        }

        if ($user->can('realization_attachments_scope_bo')) {
            return Project::active()->selectable()->whereNotIn('code', ['000H', 'APS'])->orderBy('code')->pluck('code')->all();
        }

        return [$user->project];
    }

    public function userCanViewRealization(User $user, Realization $realization): bool
    {
        $query = Realization::query()->whereKey($realization->getKey());
        $this->applyScopeToRealizationsQuery($query, $user);

        return $query->exists();
    }

    /**
     * Users appearing as realization creator or payreq requestor within scope (for filter dropdown).
     *
     * @return Collection<int, User>
     */
    public function creatorUsersForFilters(User $user): Collection
    {
        $realizationUserIds = Realization::query()
            ->select('realizations.*')
            ->join('payreqs', 'payreqs.id', '=', 'realizations.payreq_id');

        $this->applyScopeToRealizationsQuery($realizationUserIds, $user);

        $rIds = $realizationUserIds->distinct()->pluck('realizations.user_id');

        $payreqUserIds = Realization::query()
            ->select('realizations.*')
            ->join('payreqs', 'payreqs.id', '=', 'realizations.payreq_id');

        $this->applyScopeToRealizationsQuery($payreqUserIds, $user);

        $pIds = $payreqUserIds->distinct()->pluck('payreqs.user_id');

        $userIds = $rIds->merge($pIds)->unique()->filter()->values()->all();

        if ($userIds === []) {
            return new Collection;
        }

        return User::query()->whereIn('id', $userIds)->orderBy('name')->get(['id', 'name']);
    }

    /**
     * Limit creator filter to IDs that exist within scoped realizations (payreq or realization side).
     */
    public function creatorIdIsAllowedForFilters(User $user, int $creatorUserId): bool
    {
        $base = Realization::query()
            ->join('payreqs', 'payreqs.id', '=', 'realizations.payreq_id')
            ->where(function (Builder $q) use ($creatorUserId): void {
                $q->where('realizations.user_id', $creatorUserId)
                    ->orWhere('payreqs.user_id', $creatorUserId);
            });

        $this->applyScopeToRealizationsQuery($base, $user);

        return $base->exists();
    }
}
