<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Activity;
use App\Models\Realization;
use App\Models\RealizationDetail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class VerificationJournalAggregator
{
    /**
     * @param  Collection<int, Realization>  $realizations
     * @return array<int, array<string, mixed>>
     */
    public function aggregate(Collection $realizations, int $verificationJournalId, ?User $user = null): array
    {
        $realizations = $realizations->values();
        $allDetails = $realizations
            ->flatMap(fn (Realization $realization) => $realization->realizationDetails)
            ->values();

        if (! $this->hasAnyActivityTaggedDetail($allDetails)) {
            return $this->buildLegacyLines($realizations, $verificationJournalId, $user);
        }

        return $this->buildAggregatedLines($realizations, $verificationJournalId, $user);
    }

    /**
     * @param  Collection<int, RealizationDetail>  $details
     */
    public function hasAnyActivityTaggedDetail(Collection $details): bool
    {
        return $details->contains(fn (RealizationDetail $detail) => $this->effectiveActivity($detail) !== null);
    }

    public function effectiveActivity(RealizationDetail $detail): ?Activity
    {
        if ($detail->activity_excluded) {
            return null;
        }

        $activityId = $detail->activity_id ?? $detail->realization?->activity_id;
        if (! $activityId) {
            return null;
        }

        if ($detail->relationLoaded('activity') && $detail->activity_id === $activityId) {
            return $detail->activity;
        }

        if ($detail->relationLoaded('realization')
            && $detail->realization?->relationLoaded('activity')
            && $detail->activity_id === null
            && $detail->realization->activity_id === $activityId) {
            return $detail->realization->activity;
        }

        return Activity::query()->find($activityId);
    }

    public function isExpenseAccount(?Account $account): bool
    {
        return $account !== null && $account->type === 'expense';
    }

    /**
     * @return array{account_id: int, is_reclassified: bool, reclassified_reason: string|null}
     */
    public function resolveEffectiveAccount(RealizationDetail $detail): array
    {
        $originalAccount = $detail->account;
        $activity = $this->effectiveActivity($detail);

        if ($activity
            && $activity->isReklasifikasi()
            && $activity->account_id
            && $this->isExpenseAccount($originalAccount)) {
            return [
                'account_id' => (int) $activity->account_id,
                'is_reclassified' => (int) $activity->account_id !== (int) $detail->account_id,
                'reclassified_reason' => null,
            ];
        }

        $reason = null;
        if ($activity && $activity->isReklasifikasi() && $activity->account_id && ! $this->isExpenseAccount($originalAccount)) {
            $reason = 'Akun non-beban tidak direklasifikasi';
        }

        return [
            'account_id' => (int) $detail->account_id,
            'is_reclassified' => false,
            'reclassified_reason' => $reason,
        ];
    }

    /**
     * @param  Collection<int, Realization>  $realizations
     * @return array<int, array<string, mixed>>
     */
    protected function buildLegacyLines(Collection $realizations, int $verificationJournalId, ?User $user): array
    {
        $lines = [];

        foreach ($realizations as $realization) {
            $realizationDetails = $realization->realizationDetails;

            foreach ($realizationDetails as $realizationDetail) {
                $lines[] = $this->debitLine(
                    $verificationJournalId,
                    $realization,
                    $realizationDetail,
                    (int) $realizationDetail->account_id,
                    (float) $realizationDetail->amount,
                    (string) $realizationDetail->description,
                    $realizationDetail->realization->nomor,
                    false,
                    null,
                );
            }

            $lines[] = $this->creditLineForRealization(
                $verificationJournalId,
                $realization,
                $realizationDetails,
                $user,
            );
        }

        return $lines;
    }

    /**
     * @param  Collection<int, Realization>  $realizations
     * @return array<int, array<string, mixed>>
     */
    protected function buildAggregatedLines(Collection $realizations, int $verificationJournalId, ?User $user): array
    {
        $lines = [];

        foreach ($realizations as $realization) {
            $debitBuckets = [];
            $realizationDetails = $realization->realizationDetails;

            foreach ($realizationDetails as $detail) {
                $activity = $this->effectiveActivity($detail);
                $resolved = $this->resolveEffectiveAccount($detail);
                $account = Account::query()->find($resolved['account_id']);
                if (! $account) {
                    continue;
                }

                $costCenter = $detail->department?->sap_code;

                if ($activity === null) {
                    $linesKey = 'legacy:'.$detail->id;
                    $debitBuckets[$linesKey] = $this->debitLine(
                        $verificationJournalId,
                        $realization,
                        $detail,
                        (int) $detail->account_id,
                        (float) $detail->amount,
                        (string) $detail->description,
                        $realization->nomor,
                        false,
                        null,
                    );

                    continue;
                }

                $bucketKey = implode('|', [
                    $resolved['account_id'],
                    (string) $costCenter,
                    (string) $activity->id,
                ]);

                if (! isset($debitBuckets[$bucketKey])) {
                    $debitBuckets[$bucketKey] = $this->debitLine(
                        $verificationJournalId,
                        $realization,
                        $detail,
                        $resolved['account_id'],
                        0.0,
                        $activity->name,
                        $realization->nomor,
                        $resolved['is_reclassified'],
                        $resolved['reclassified_reason'],
                        $activity->id,
                    );
                    $debitBuckets[$bucketKey]['account_code'] = $account->account_number;
                }

                $debitBuckets[$bucketKey]['amount'] = round(
                    (float) $debitBuckets[$bucketKey]['amount'] + (float) $detail->amount,
                    2
                );

                if ($resolved['reclassified_reason'] && empty($debitBuckets[$bucketKey]['reclassified_reason'])) {
                    $debitBuckets[$bucketKey]['reclassified_reason'] = $resolved['reclassified_reason'];
                }
            }

            foreach ($debitBuckets as $debitLine) {
                $lines[] = $debitLine;
            }

            $lines[] = $this->creditLineForRealization(
                $verificationJournalId,
                $realization,
                $realizationDetails,
                $user,
                true,
            );
        }

        return $lines;
    }

    /**
     * @return array<string, mixed>
     */
    protected function debitLine(
        int $verificationJournalId,
        Realization $realization,
        RealizationDetail $detail,
        int $accountId,
        float $amount,
        string $description,
        string $realizationNo,
        bool $isReclassified,
        ?string $reclassifiedReason,
        ?int $activityId = null,
    ): array {
        $account = Account::query()->find($accountId);

        return [
            'verification_journal_id' => $verificationJournalId,
            'realization_date' => Carbon::parse($realization->created_at)->format('Y-m-d'),
            'debit_credit' => 'debit',
            'realization_no' => $realizationNo,
            'account_code' => $account?->account_number ?? '',
            'amount' => $amount,
            'description' => $description,
            'project' => $detail->project,
            'cost_center' => $detail->department?->sap_code,
            'is_reclassified' => $isReclassified,
            'reclassified_reason' => $reclassifiedReason,
            'activity_id' => $activityId,
        ];
    }

    /**
     * @param  Collection<int, RealizationDetail>  $realizationDetails
     * @return array<string, mixed>
     */
    protected function creditLineForRealization(
        int $verificationJournalId,
        Realization $realization,
        Collection $realizationDetails,
        ?User $user,
        bool $withActivitySuffix = false,
    ): array {
        $cashAccount = $this->resolveCashAccount($realization, $user);
        $descriptions = $withActivitySuffix
            ? $this->buildCreditDescriptionWithActivity($realizationDetails)
            : $this->buildLegacyCreditDescription($realizationDetails);

        return [
            'verification_journal_id' => $verificationJournalId,
            'realization_date' => Carbon::parse($realization->created_at)->format('Y-m-d'),
            'debit_credit' => 'credit',
            'realization_no' => $realization->nomor,
            'account_code' => $cashAccount?->account_number ?? '',
            'amount' => (float) $realizationDetails->sum('amount'),
            'description' => $descriptions,
            'project' => $realization->project,
            'cost_center' => $realization->department?->sap_code,
            'is_reclassified' => false,
            'reclassified_reason' => null,
            'activity_id' => null,
        ];
    }

    /**
     * @param  Collection<int, RealizationDetail>  $realizationDetails
     */
    protected function buildLegacyCreditDescription(Collection $realizationDetails): string
    {
        $arrayDesc = $realizationDetails->pluck('description')->unique();
        $descriptions = implode(', ', $arrayDesc->toArray());
        if (strlen($descriptions) > 100) {
            $descriptions = substr($descriptions, 0, 100);
        }

        return $descriptions;
    }

    /**
     * @param  Collection<int, RealizationDetail>  $realizationDetails
     */
    protected function buildCreditDescriptionWithActivity(Collection $realizationDetails): string
    {
        $firstDetail = $realizationDetails->first();
        $description = $firstDetail ? (string) $firstDetail->description : '';

        $activity = null;
        foreach ($realizationDetails as $detail) {
            $effective = $this->effectiveActivity($detail);
            if ($effective !== null) {
                $activity = $effective;
                break;
            }
        }

        if ($activity !== null) {
            $description .= ' · Kegiatan: '.$activity->name;
        }

        return $this->truncateCreditDescription($description);
    }

    protected function truncateCreditDescription(string $description): string
    {
        $maxLength = 180;
        if (strlen($description) <= $maxLength) {
            return $description;
        }

        return rtrim(substr($description, 0, $maxLength - 1), ' ').'…';
    }

    protected function resolveCashAccount(Realization $realization, ?User $user): ?Account
    {
        if ($user && ($user->project === '000H' || $user->project === 'APS')) {
            $cashProject = '000H';
        } else {
            $cashProject = $realization->project;
        }

        return Account::query()
            ->selectable()
            ->where('type', 'cash')
            ->where('project', $cashProject)
            ->orderBy('account_number')
            ->first();
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<int, array<string, mixed>>
     */
    public function stripInternalMetadata(array $lines): array
    {
        return array_map(function (array $line) {
            unset($line['is_reclassified'], $line['reclassified_reason']);

            return $line;
        }, $lines);
    }
}
