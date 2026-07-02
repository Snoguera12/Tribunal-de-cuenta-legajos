<?php

namespace App\Policies;

use App\Models\Persona;
use App\Models\Titulo;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TituloPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin_RRHH_Funcionario();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user): bool
    {
        return $user->isAdmin_RRHH_Funcionario();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Persona $persona): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Titulo $titulo): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Titulo $titulo): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Titulo $titulo): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Titulo $titulo): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }
}
