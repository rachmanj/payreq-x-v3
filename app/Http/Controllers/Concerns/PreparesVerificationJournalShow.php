<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Account;
use App\Models\SapSubmissionLog;
use App\Models\VerificationJournal;
use App\Models\VerificationJournalDetail;
use App\Support\VerificationJournalDetailDescriptionEnricher;

trait PreparesVerificationJournalShow
{
    protected function verificationJournalShowData(int $id): array
    {
        $user = auth()->user();
        $vj = VerificationJournal::find($id);

        if (! $vj) {
            abort(404);
        }

        $this->assertProjectAccessible($user, $vj->project);

        $canSubmitToSap = $this->canSubmitToSap($user, $vj);
        $canReverseSap = $this->canReverseSap($user);
        $canValidateVj = $user->can('validate_vj');
        $canManageSapInfo = $this->canManageSapInfo($user);
        $canEditVjDetails = $this->canEditVjDetails($user, $vj);
        $canManageSapInfoForVj = $this->canManageSapInfoForVj($user, $vj);

        $vj_details = VerificationJournalDetail::where('verification_journal_id', $id)
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($detail) {
                $account = Account::where('account_number', $detail->account_code)->first();
                $detail->account_name = $account ? $account->account_name : 'not found';

                return VerificationJournalDetailDescriptionEnricher::enrich($detail);
            });

        $submissionLogs = SapSubmissionLog::where('verification_journal_id', $vj->id)
            ->orderBy('created_at', 'desc')
            ->with('user')
            ->get();

        return compact(
            'vj',
            'vj_details',
            'submissionLogs',
            'canSubmitToSap',
            'canReverseSap',
            'canValidateVj',
            'canManageSapInfo',
            'canEditVjDetails',
            'canManageSapInfoForVj',
        );
    }

    protected function isBoRestrictedUser($user): bool
    {
        $fullAccessRoles = ['superadmin', 'admin', 'cashier', 'approver'];
        $boRoles = ['approver_bo', 'cashier_bo'];

        return $user->hasAnyRole($boRoles) && ! $user->hasAnyRole($fullAccessRoles);
    }

    protected function assertProjectAccessible($user, string $project): void
    {
        if ($this->isBoRestrictedUser($user) && $project !== '001H') {
            abort(403, 'You do not have permission to access this project.');
        }
    }

    protected function canSubmitToSap($user, ?VerificationJournal $vj = null): bool
    {
        $allowedRoles = ['superadmin', 'admin', 'cashier', 'approver'];

        if ($user->hasAnyRole($allowedRoles)) {
            return true;
        }

        if ($user->hasAnyRole(['approver_bo', 'cashier_bo'])) {
            return $vj !== null && $vj->project === '001H';
        }

        return false;
    }

    protected function canManageSapInfo($user): bool
    {
        return $user->hasAnyRole(['superadmin', 'admin', 'cashier']);
    }

    protected function canManageSapInfoForVj($user, VerificationJournal $vj): bool
    {
        return $this->canManageSapInfo($user)
            && $vj->validation_status === VerificationJournal::VALIDATION_VALIDATED;
    }

    protected function canEditVjDetails($user, VerificationJournal $vj): bool
    {
        if ($vj->sap_journal_no) {
            return false;
        }

        if ($this->isBoRestrictedUser($user) && $vj->project !== '001H') {
            return false;
        }

        if ($vj->created_by === $user->id) {
            return true;
        }

        return $user->can('edit_verification_project');
    }

    protected function canReverseSap($user): bool
    {
        return $user->can('cancel_sap_journal');
    }
}
