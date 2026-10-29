<?php

namespace App\Policies;

use App\Models\Estudio;
use App\Models\User;

class EstudioPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isRRHH() || $user->isFuncionario();
    }

    public function view(User $user, Estudio $estudio): bool
    {
        return $user->isAdmin() || $user->isRRHH() || $user->isFuncionario();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    public function update(User $user, Estudio $estudio): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    public function delete(User $user, Estudio $estudio): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    public function restore(User $user, Estudio $estudio): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    public function forceDelete(User $user, Estudio $estudio): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }
}
