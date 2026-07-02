<?php

namespace App\Policies;

use App\Models\Estudio;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EstudioPolicy
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
    public function view(User $user, Estudio $estudio): bool
    {
        return $user->isAdmin() || $user->isRRHH() || $user->isFuncionario();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Persona $persona): bool
    {
        return $user->isAdmin() || $user->isRRHH() || $user->persona_id === $persona->id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Estudio $estudio): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Estudio $estudio): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Estudio $estudio): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Estudio $estudio): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }
}
