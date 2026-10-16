<?php

namespace App\Policies;

use App\Models\Area;
use App\Models\User;

class AreaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isRRHH() || $user->isFuncionario();
    }

    public function view(User $user, Area $area): bool
    {
        return $user->isAdmin() || $user->isRRHH() || $user->isFuncionario();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    public function update(User $user, Area $area): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    public function delete(User $user, Area $area): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    public function restore(User $user, Area $area): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    public function forceDelete(User $user, Area $area): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }
}
