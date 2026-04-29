<?php

namespace App\Policies;

use App\Http\Controllers\UserController;
use App\Models\Anggaran;
use App\Models\User;

class AnggaranPolicy
{
    public function view(User $user, Anggaran $anggaran): bool
    {
        $roles = app(UserController::class)->getUserRoles();

        if (array_intersect(['superadmin', 'admin'], $roles)) {
            return true;
        }

        if (in_array('cashier', $roles, true) && $anggaran->project === $user->project) {
            return true;
        }

        if ((int) $anggaran->created_by === (int) $user->id) {
            return true;
        }

        if ($anggaran->usage === 'project'
            && $anggaran->project === $user->project
            && in_array($anggaran->status, ['approved', 'close'], true)
            && $anggaran->is_active
        ) {
            return true;
        }

        if ($anggaran->usage === 'department'
            && (int) $anggaran->department_id === (int) $user->department_id
            && in_array($anggaran->status, ['approved', 'close'], true)
            && $anggaran->is_active
        ) {
            return true;
        }

        return false;
    }

    public function editThroughPayreq(User $user, Anggaran $anggaran): bool
    {
        return $this->view($user, $anggaran)
            && (bool) $anggaran->editable
            && (int) $anggaran->created_by === (int) $user->id;
    }
}
