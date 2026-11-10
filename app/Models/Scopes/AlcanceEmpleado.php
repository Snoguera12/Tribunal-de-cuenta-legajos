<?php

namespace App\Models\Scopes;

use App\Models\Documento;
use App\Models\Legajo;
use App\Models\Licencia;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Un usuario con rol Empleado solo puede alcanzar sus propios datos, sin importar
 * por dónde llegue (panel, descarga de documentos, relaciones, enlaces con otro ID).
 * El resto de los roles no se filtra. En consola y en la API no hay usuario web, así que no aplica.
 */
class AlcanceEmpleado implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $usuario = auth()->user();

        if (! $usuario instanceof User || ! $usuario->isEmpleado()) {
            return;
        }

        $personaId = $usuario->persona_id;

        // sin persona vinculada no ve nada (where(col, null) sería "IS NULL" y mostraría registros sueltos)
        if ($personaId === null) {
            $builder->whereRaw('1 = 0');

            return;
        }

        match (true) {
            $model instanceof Persona => $builder->where($model->qualifyColumn('id'), $personaId),
            $model instanceof Legajo => $builder->where($model->qualifyColumn('persona_id'), $personaId),
            $model instanceof Documento => $builder->whereHas('legajos', fn (Builder $q) => $q->where('legajos.persona_id', $personaId)),
            $model instanceof Licencia => $builder->whereHas('documento.legajos', fn (Builder $q) => $q->where('legajos.persona_id', $personaId)),
            default => null,
        };
    }
}
