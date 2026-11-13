<?php

namespace App\Policies;

use App\Models\Legajo;
use App\Models\User;

class LegajoPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isStaffRoles();
    }

    public function view(User $user, Legajo $legajo): bool
    {
        if ($user->isStaffRoles()) {
            return $user->isStaffRoles();
        }

        return $user->persona_id !== null && $user->persona_id === $legajo->persona_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin_RRHH();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Legajo $legajo): bool
    {
        return $user->isAdmin_RRHH() && $legajo->isAlta();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Legajo $legajo): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Legajo $legajo): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Legajo $legajo): bool
    {
        return false;
    }
}
