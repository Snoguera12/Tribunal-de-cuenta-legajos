<?php

namespace App\Policies;

<<<<<<< HEAD
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

// solo el admin puede ver la auditoria, y nadie la puede editar/borrar
class AuditoriaPolicy
{
=======
use App\Models\Auditoria;
use App\Models\User;


// solo el administrador puede ver la auditoria y nadie la puede cambiar
class AuditoriaPolicy
{

>>>>>>> version2-20261014_210855
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

<<<<<<< HEAD
    public function view(User $user, Activity $activity): bool
=======

    public function view(User $user, Auditoria $auditoria): bool
>>>>>>> version2-20261014_210855
    {
        return $user->isAdmin();
    }

<<<<<<< HEAD
=======

>>>>>>> version2-20261014_210855
    public function create(User $user): bool
    {
        return false;
    }

<<<<<<< HEAD
    public function update(User $user, Activity $activity): bool
=======

    public function update(User $user, Auditoria $auditoria): bool
>>>>>>> version2-20261014_210855
    {
        return false;
    }

<<<<<<< HEAD
    public function delete(User $user, Activity $activity): bool
=======

    public function delete(User $user, Auditoria $auditoria): bool
>>>>>>> version2-20261014_210855
    {
        return false;
    }

<<<<<<< HEAD
    public function restore(User $user, Activity $activity): bool
=======

    public function deleteAny(User $user): bool
>>>>>>> version2-20261014_210855
    {
        return false;
    }

<<<<<<< HEAD
    public function forceDelete(User $user, Activity $activity): bool
    {
        return false;
    }
=======
>>>>>>> version2-20261014_210855
}
