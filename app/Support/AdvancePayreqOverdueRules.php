<?php

namespace App\Support;

use App\Models\Payreq;
use Illuminate\Database\Eloquent\Builder;

/**
 * Single source of truth for advance payreq overdue guards and outstanding (ongoing) lists.
 */
class AdvancePayreqOverdueRules
{
    /** @var list<string> */
    public const REALIZATION_COMPLETE_STATUSES = ['close', 'verification-complete'];

    public static function isAdvanceRealizationFinished(Payreq $payreq): bool
    {
        if ($payreq->type !== 'advance') {
            return false;
        }

        if ($payreq->status === 'close') {
            return true;
        }

        $realization = $payreq->relationLoaded('realization')
            ? $payreq->realization
            : $payreq->realization()->first();

        if (! $realization) {
            return false;
        }

        if (in_array($realization->status, self::REALIZATION_COMPLETE_STATUSES, true)) {
            return true;
        }

        if ($realization->verification_journal_id) {
            $verificationJournal = $realization->relationLoaded('verificationJournal')
                ? $realization->verificationJournal
                : $realization->verificationJournal()->first();

            if ($verificationJournal && filled($verificationJournal->sap_journal_no)) {
                return true;
            }
        }

        return false;
    }

    public static function isAdvanceOutstanding(Payreq $payreq): bool
    {
        return $payreq->type === 'advance'
            && $payreq->status === 'paid'
            && ! self::isAdvanceRealizationFinished($payreq);
    }

    public static function restrictToOutstandingAdvance(Builder $query): Builder
    {
        return self::whereAdvanceRealizationNotFinished($query, ['paid']);
    }

    public static function restrictToOutstandingAdvanceIncludingSplit(Builder $query): Builder
    {
        return self::whereAdvanceRealizationNotFinished($query, ['paid', 'split']);
    }

    public static function restrictToAdvanceStillOverdue(Builder $query): Builder
    {
        return self::whereAdvanceRealizationNotFinished($query, ['paid'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now());
    }

    /**
     * @param  list<string>  $statuses
     */
    private static function whereAdvanceRealizationNotFinished(Builder $query, array $statuses): Builder
    {
        return $query
            ->where('type', 'advance')
            ->whereIn('status', $statuses)
            ->where(function (Builder $outer) {
                $outer->whereDoesntHave('realization')
                    ->orWhereHas('realization', function (Builder $realizationQuery) {
                        $realizationQuery
                            ->whereNotIn('status', self::REALIZATION_COMPLETE_STATUSES)
                            ->where(function (Builder $inner) {
                                $inner->whereNull('verification_journal_id')
                                    ->orWhereHas('verificationJournal', function (Builder $vjQuery) {
                                        $vjQuery->where(function (Builder $sapQuery) {
                                            $sapQuery->whereNull('sap_journal_no')
                                                ->orWhere('sap_journal_no', '');
                                        });
                                    });
                            });
                    });
            });
    }

    public static function isAdvanceStillOverdue(Payreq $payreq): bool
    {
        if ($payreq->type !== 'advance' || $payreq->status !== 'paid' || ! $payreq->due_date) {
            return false;
        }

        if (! \Carbon\Carbon::parse($payreq->due_date)->lt(now())) {
            return false;
        }

        return ! self::isAdvanceRealizationFinished($payreq);
    }
}
