<?php

namespace App\Services;

use App\Models\Anggaran;
use App\Models\Payreq;

final class PayreqBudgetSubmitValidator
{
    public function validate(Payreq $payreq): ?string
    {
        if (! in_array($payreq->type, ['advance', 'reimburse'], true)) {
            return null;
        }

        if ($payreq->isAdvanceMultiBudget()) {
            return $this->validateMultiAllocation($payreq);
        }

        return $this->validateLegacyRab($payreq);
    }

    private function validateLegacyRab(Payreq $payreq): ?string
    {
        if (! $payreq->rab_id) {
            return 'RAB harus diisi, payreq belum bisa disubmit';
        }

        $isValid = Anggaran::query()
            ->where('id', $payreq->rab_id)
            ->where('status', 'approved')
            ->where('is_active', 1)
            ->exists();

        if (! $isValid) {
            return 'RAB tidak valid atau tidak aktif';
        }

        return null;
    }

    private function validateMultiAllocation(Payreq $payreq): ?string
    {
        $payreq->loadMissing('anggaranAllocations');

        if ($payreq->anggaranAllocations->count() < 1) {
            return 'Alokasi anggaran minimal satu baris, payreq belum bisa disubmit';
        }

        $sumAlloc = round((float) $payreq->anggaranAllocations->sum('amount'), 2);
        $sumPayreq = round((float) $payreq->amount, 2);

        if (abs($sumAlloc - $sumPayreq) > 0.009) {
            return 'Jumlah alokasi baris tidak sama dengan total payreq';
        }

        return null;
    }
}
