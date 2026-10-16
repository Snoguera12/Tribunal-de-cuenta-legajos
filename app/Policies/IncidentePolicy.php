<?php

namespace App\Policies;

use App\Models\Incidente;
use App\Models\User;


// solo el administrador, ningun otro rol
class IncidentePolicy
{

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }


    public function view(User $user, Incidente $incidente): bool
    {
        return $user->isAdmin();
    }


    public function create(User $user): bool
    {
        return false;
    }


    // solo para cambiar estado y notas
    public function update(User $user, Incidente $incidente): bool
    {
        return $user->isAdmin();
    }


    public function delete(User $user, Incidente $incidente): bool
    {
        return false;
    }


    public function deleteAny(User $user): bool
    {
        return false;
    }

}
