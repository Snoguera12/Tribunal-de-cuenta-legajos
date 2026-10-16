<?php

namespace App\Policies;

use App\Models\Documento;
use App\Models\User;

class DocumentoPolicy
{
    public function viewAny(User $user): bool
    {
        // el empleado tambien puede entrar, pero solo ve los suyos
        return $user->isAdmin() || $user->isRRHH() || $user->isFuncionario() || $user->isEmpleado();
    }

    public function view(User $user, Documento $documento): bool
    {
        if ($user->isAdmin() || $user->isRRHH() || $user->isFuncionario()) {
            return true;
        }

        // empleado: solo si es su documento
        return $user->persona_id !== null
            && $documento->legajo
            && $documento->legajo->persona_id === $user->persona_id;
    }

    public function create(User $user): bool
    {
        // el empleado ve pero no sube
        return $user->isAdmin() || $user->isRRHH();
    }

    public function update(User $user, Documento $documento): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    public function delete(User $user, Documento $documento): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    public function restore(User $user, Documento $documento): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }

    public function forceDelete(User $user, Documento $documento): bool
    {
        return $user->isAdmin() || $user->isRRHH();
    }
}
