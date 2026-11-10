<?php

namespace App\Policies;

use App\Models\Documento;
use App\Models\User;

class DocumentoPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isStaffRoles();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Documento $documento): bool
    {
        if ($user->isStaffRoles()) {
            return $user->isStaffRoles();
        }

        return filled($user->persona_id) && $documento->legajos()->where('legajos.persona_id', $user->persona_id)->exists();
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
    public function update(User $user, Documento $documento): bool
    {
        return $user->isAdmin_RRHH();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Documento $documento): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Documento $documento): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Documento $documento): bool
    {
        return false;
    }
}
