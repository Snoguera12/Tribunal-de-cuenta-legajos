<?php

namespace App\Policies;

use App\Models\Cargo;
use App\Models\User;

class CargoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isRRHH() || $user->isFuncionario();
    }

    public function view(User $user, Cargo $cargo): bool
    {
        return $user->isAdmin() || $user->isRRHH() || $user->isFuncionario();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    public function update(User $user, Cargo $cargo): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    public function delete(User $user, Cargo $cargo): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    public function restore(User $user, Cargo $cargo): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    public function forceDelete(User $user, Cargo $cargo): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }
}
