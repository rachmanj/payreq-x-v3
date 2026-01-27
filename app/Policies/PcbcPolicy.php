<?php

namespace App\Policies;

use App\Models\Pcbc;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PcbcPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['superadmin', 'admin', 'cashier']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Pcbc $pcbc): bool
    {
        return $user->hasAnyRole(['superadmin', 'admin', 'cashier']) 
            || $pcbc->created_by === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['superadmin', 'admin', 'cashier']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Pcbc $pcbc): bool
    {
        return $user->hasAnyRole(['superadmin', 'admin', 'cashier']) 
            || $pcbc->created_by === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Pcbc $pcbc): bool
    {
        return $user->hasAnyRole(['superadmin', 'admin', 'cashier']) 
            || $pcbc->created_by === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Pcbc $pcbc): bool
    {
        return $user->hasAnyRole(['superadmin', 'admin']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Pcbc $pcbc): bool
    {
        return $user->hasAnyRole(['superadmin']);
    }
}
