<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    public function view(User $user, User $model): bool
    {
        if ($model->rol === 'administrador' && !$user->isAdmin()) {
            return false;
        }

        return $user->isAdmin() || $user->isRRHH();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    public function update(User $user, User $model): bool
    {
        // -mejor seguridad- ni rrhh puede tocar al administrador
        if ($model->rol === 'administrador' && !$user->isAdmin()) {
            return false;
        }

        return $user->isAdmin() || $user->isRRHH();
    }

    public function delete(User $user, User $model): bool
    {
        if ($model->rol === 'administrador' && !$user->isAdmin()) {
            return false;
        }

        return $user->isAdmin() || $user->isRRHH();
    }

    public function restore(User $user, User $model): bool
    {
        if ($model->rol === 'administrador' && !$user->isAdmin()) {
            return false;
        }

        return $user->isAdmin() || $user->isRRHH();
    }

    public function forceDelete(User $user, User $model): bool
    {
        if ($model->rol === 'administrador' && !$user->isAdmin()) {
            return false;
        }

        return $user->isAdmin() || $user->isRRHH();
    }
}
