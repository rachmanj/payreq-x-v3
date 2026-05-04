<?php

namespace App\Services;

use App\Models\Payreq;

class PayreqRealizationBudgetWarningService
{
    /**
     * @return list<string>
     */
    public function messages(Payreq $payreq): array
    {
        if (! $payreq->isAdvanceMultiBudget()) {
            return [];
        }

        $payreq->loadMissing(['anggaranAllocations', 'realization.realizationDetails']);

        $allocatedByAnggaran = [];
        foreach ($payreq->anggaranAllocations as $row) {
            $id = (int) $row->anggaran_id;
            $allocatedByAnggaran[$id] = ($allocatedByAnggaran[$id] ?? 0) + (float) $row->amount;
        }

        $realizedByAnggaran = [];
        if ($payreq->realization) {
            foreach ($payreq->realization->realizationDetails as $detail) {
                if ($detail->rab_id === null) {
                    continue;
                }
                $id = (int) $detail->rab_id;
                $realizedByAnggaran[$id] = ($realizedByAnggaran[$id] ?? 0) + (float) $detail->amount;
            }
        }

        $warnings = [];
        foreach ($allocatedByAnggaran as $id => $allocated) {
            $realized = $realizedByAnggaran[$id] ?? 0.0;
            $diff = round($realized - $allocated, 2);
            if (abs($diff) > 0.009) {
                $warnings[] = sprintf(
                    'Anggaran #%d — allocation %s vs realization %s (difference %s).',
                    $id,
                    number_format($allocated, 2, '.', ','),
                    number_format($realized, 2, '.', ','),
                    number_format($diff, 2, '.', ',')
                );
            }
        }

        foreach ($realizedByAnggaran as $id => $realized) {
            if (! array_key_exists($id, $allocatedByAnggaran) && abs($realized) > 0.009) {
                $warnings[] = sprintf(
                    'Realization uses Anggaran #%d (%s) with no matching advance allocation row.',
                    $id,
                    number_format($realized, 2, '.', ',')
                );
            }
        }

        return $warnings;
    }
}
