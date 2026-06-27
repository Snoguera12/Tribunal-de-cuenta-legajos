<?php

namespace App\Policies;

use App\Models\Historialbaja;
use App\Models\User;

class HistorialbajaPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isRRHH() || $user->isFuncionario();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Historialbaja $historialbaja): bool
    {
        return $user->isAdmin() || $user->isRRHH() || $user->isFuncionario();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Historialbaja $historialbaja): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Historialbaja $historialbaja): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Historialbaja $historialbaja): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Historialbaja $historialbaja): bool
    {
        return false;
    }
}
