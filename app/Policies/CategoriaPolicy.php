<?php

namespace App\Policies;

use App\Models\Categoria;
use App\Models\User;

class CategoriaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin_RRHH() || $user->isFuncionario();
    }

    public function view(User $user, Categoria $categoria): bool
    {
        return $user->isAdmin_RRHH() || $user->isFuncionario();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin_RRHH();
    }

    public function update(User $user, Categoria $categoria): bool
    {
        return $user->isAdmin_RRHH();
    }

    public function delete(User $user, Categoria $categoria): bool
    {
        return false;
    }

    public function restore(User $user, Categoria $categoria): bool
    {
        return false;
    }

    public function forceDelete(User $user, Categoria $categoria): bool
    {
        return false;
    }
}