<?php

namespace App\Policies;

use App\Models\Historialbaja;
use App\Models\User;

class HistorialbajaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isRRHH() || $user->isFuncionario();
    }

    public function view(User $user, Historialbaja $historialbaja): bool
    {
        return $user->isAdmin() || $user->isRRHH() || $user->isFuncionario();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Historialbaja $historialbaja): bool
    {
        return false;
    }

    public function delete(User $user, Historialbaja $historialbaja): bool
    {
        return false;
    }

    public function restore(User $user, Historialbaja $historialbaja): bool
    {
        return false;
    }

    public function forceDelete(User $user, Historialbaja $historialbaja): bool
    {
        return false;
    }
}
