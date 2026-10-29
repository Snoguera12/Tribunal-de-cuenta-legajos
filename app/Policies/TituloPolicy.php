<?php

namespace App\Policies;

use App\Models\Titulo;
use App\Models\User;

class TituloPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Titulo $titulo): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Titulo $titulo): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Titulo $titulo): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Titulo $titulo): bool
    {
        return $user->isAdmin();
    }
}
