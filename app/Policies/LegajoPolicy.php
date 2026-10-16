<?php

namespace App\Policies;

use App\Models\Legajo;
use App\Models\User;

class LegajoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isRRHH() || $user->isFuncionario();
    }

    public function view(User $user, Legajo $legajo): bool
    {
        if ($user->isAdmin() || $user->isRRHH() || $user->isFuncionario()) {
            return true;
        }

        return $user->persona_id !== null && $user->persona_id === $legajo->persona_id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    public function update(User $user, Legajo $legajo): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    public function delete(User $user, Legajo $legajo): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    public function restore(User $user, Legajo $legajo): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    public function forceDelete(User $user, Legajo $legajo): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }
}
