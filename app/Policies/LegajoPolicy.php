<?php

namespace App\Policies;

use App\Models\Legajo;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LegajoPolicy
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
    public function view(User $user, Legajo $legajo): bool
    {
        return $user->isAdmin() || $user->isRRHH() || $user->isFuncionario();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Legajo $legajo): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Legajo $legajo): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Legajo $legajo): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Legajo $legajo): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }
}
