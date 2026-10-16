<?php

namespace App\Policies;

use App\Models\Auditoria;
use App\Models\User;


// solo el administrador puede ver la auditoria y nadie la puede cambiar
class AuditoriaPolicy
{

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }


    public function view(User $user, Auditoria $auditoria): bool
    {
        return $user->isAdmin();
    }


    public function create(User $user): bool
    {
        return false;
    }


    public function update(User $user, Auditoria $auditoria): bool
    {
        return false;
    }


    public function delete(User $user, Auditoria $auditoria): bool
    {
        return false;
    }


    public function deleteAny(User $user): bool
    {
        return false;
    }

}
