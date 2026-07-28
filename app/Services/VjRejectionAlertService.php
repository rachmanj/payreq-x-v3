<?php

namespace App\Services;

use App\Models\User;
use App\Models\VerificationJournal;
use Illuminate\Support\Collection;

class VjRejectionAlertService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getAlertsFor(User $user): Collection
    {
        return VerificationJournal::query()
            ->where('created_by', $user->id)
            ->where('validation_status', VerificationJournal::VALIDATION_REJECTED)
            ->with('validatedBy')
            ->latest('validated_at')
            ->get()
            ->map(fn (VerificationJournal $vj) => [
                'id' => $vj->id,
                'nomor' => $vj->nomor,
                'project' => $vj->project,
                'type' => $vj->type,
                'rejection_reason' => $vj->rejection_reason,
                'rejected_by' => $vj->validatedBy->name,
                'rejected_at' => $vj->validated_at?->format('d-M-Y H:i'),
                'url' => $this->fixUrlFor($vj),
            ]);
    }

    protected function fixUrlFor(VerificationJournal $vj): string
    {
        if ($vj->type === 'bank') {
            return route('cashier.bank-transactions.edit', $vj->id);
        }

        return route('accounting.sap-sync.edit_vjdetail_display', ['vj_id' => $vj->id]);
    }
}
