<?php

namespace App\Policies;

use App\Models\Bilyet;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BilyetPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view bilyets list
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Bilyet $bilyet): bool
    {
        return $user->hasRole(['admin', 'superadmin']) ||
            $bilyet->project === $user->project;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('add_bilyet');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Bilyet $bilyet): bool
    {
        return $this->view($user, $bilyet) &&
            $bilyet->status !== 'cair'; // Cannot modify after cair
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Bilyet $bilyet): bool
    {
        return $this->view($user, $bilyet) &&
            $bilyet->status === 'onhand' && // Only delete onhand bilyets
            $user->can('delete_bilyet');
    }

    /**
     * Determine whether the user can void the model.
     */
    public function void(User $user, Bilyet $bilyet): bool
    {
        return $this->view($user, $bilyet) &&
            $bilyet->canBeVoided();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Bilyet $bilyet): bool
    {
        return false; // No restore functionality for bilyets
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Bilyet $bilyet): bool
    {
        return $user->hasRole(['superadmin']) &&
            $bilyet->status === 'onhand';
    }
}
