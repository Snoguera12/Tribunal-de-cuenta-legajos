<?php

namespace App\Policies;

use App\Models\Persona;
use App\Models\User;

class PersonaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaffRoles() || $user->isEmpleado();
    }

    public function view(User $user, Persona $persona): bool
    {
        if ($user->isAdmin() || $user->isRRHH()) {
            return true;
        }

        return $user->persona_id !== null && $user->persona_id === $persona->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    public function update(User $user, Persona $persona): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    public function delete(User $user, Persona $persona): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    public function restore(User $user, Persona $persona): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    public function forceDelete(User $user, Persona $persona): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }
}