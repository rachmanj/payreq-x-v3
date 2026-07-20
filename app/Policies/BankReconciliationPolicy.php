<?php

namespace App\Policies;

use App\Models\BankReconciliation;
use App\Models\Giro;
use App\Models\User;

class BankReconciliationPolicy
{
    /**
     * Roles that can access reconciliations across all projects.
     *
     * @var list<string>
     */
    public const ELEVATED_ROLES = ['admin', 'superadmin', 'cashier', 'approver_bo', 'cashier_bo', 'corsec'];

    public function viewAny(User $user): bool
    {
        return $user->can('akses_koran');
    }

    public function view(User $user, BankReconciliation $bankReconciliation): bool
    {
        if (! $user->can('akses_koran')) {
            return false;
        }

        $bankReconciliation->loadMissing('giro');

        if ($bankReconciliation->giro === null) {
            return false;
        }

        return $this->canAccessGiro($user, $bankReconciliation->giro);
    }

    public function create(User $user): bool
    {
        return $user->can('akses_koran');
    }

    public function update(User $user, BankReconciliation $bankReconciliation): bool
    {
        return $this->view($user, $bankReconciliation);
    }

    public function submit(User $user, BankReconciliation $bankReconciliation): bool
    {
        return $this->update($user, $bankReconciliation);
    }

    public function validate(User $user, BankReconciliation $bankReconciliation): bool
    {
        if (! $this->view($user, $bankReconciliation)) {
            return false;
        }

        if (! $user->can('validate_bank_reconciliation')) {
            return false;
        }

        return ! $bankReconciliation->isPreparer((int) $user->id);
    }

    public function accessGiro(User $user, Giro $giro): bool
    {
        if (! $user->can('akses_koran')) {
            return false;
        }

        return $this->canAccessGiro($user, $giro);
    }

    protected function canAccessGiro(User $user, Giro $giro): bool
    {
        if ($user->hasAnyRole(self::ELEVATED_ROLES)) {
            return true;
        }

        return $giro->project === $user->project;
    }
}
