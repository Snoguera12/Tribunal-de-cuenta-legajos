<?php

namespace App\Policies;

use App\Models\Oficina;
use App\Models\User;

class OficinaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isRRHH() || $user->isFuncionario();
    }

    public function view(User $user, Oficina $oficina): bool
    {
        return $user->isAdmin() || $user->isRRHH() || $user->isFuncionario();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    public function update(User $user, Oficina $oficina): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    public function delete(User $user, Oficina $oficina): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    public function restore(User $user, Oficina $oficina): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    public function forceDelete(User $user, Oficina $oficina): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }
}
