<?php

namespace App\Policies;

use App\Models\Persona;
use App\Models\User;

class PersonaPolicy
{
    public function viewAny(): bool
    {
        return true;
    }

    public function view(): bool
    {
        return true;
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