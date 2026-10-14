<?php

namespace App\Policies;

use App\Models\Licencia;
use App\Models\User;

class LicenciaPolicy
{
    /**
     * Ver el listado y el historial de licencias.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    public function view(User $user, Licencia $licencia): bool
    {
        if ($user->isAdmin() || $user->isRRHH()) {
            return true;
        }

        return $user->persona_id !== null
            && $licencia->legajo?->persona_id === $user->persona_id;
    }

    // Las licencias entran solo por la API: no se crean ni se editan a mano.
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Licencia $licencia): bool
    {
        return false;
    }

    public function delete(User $user, Licencia $licencia): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Licencia $licencia): bool
    {
        return false;
    }

    public function forceDelete(User $user, Licencia $licencia): bool
    {
        return false;
    }
}
