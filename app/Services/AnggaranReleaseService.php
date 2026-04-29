<?php

namespace App\Services;

use App\Models\Anggaran;
use App\Models\Payreq;
use App\Models\Project;
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
     * Payreqs linked to this RAB (respecting migrated old_rab_id) that have outgoings.
     *
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
     * @return array{amount: float|int|string, persen: float|int|string}
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
