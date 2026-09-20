<?php

namespace App\Models\Helpers;

use App\Models\Persona;
use App\Models\User;

class PersonaHelper{
    public static function getPersonasUsuario($persona_id = null): array
    {
        // 1. Obtenemos los IDs de las personas que YA tienen un usuario asignado
        $personasAsignadasIds = User::whereNotNull('persona_id')->pluck('persona_id');

        // 2. Si hay un ID seleccionado actualmente, lo excluimos de la lista de restricciones
        // para que esa persona en específico SÍ aparezca como opción elegible
        if ($persona_id) {
            $personasAsignadasIds = $personasAsignadasIds->reject(fn($id) => $id == $persona_id);
        }

        // 3. Traemos las personas que no están asignadas (más la seleccionada, si aplica)
        $resultado = Persona::whereNotIn('id', $personasAsignadasIds)
        ->select('id', 'nombre', 'apellido', 'dni')
        ->orderBy('id')
        ->get();
            
        // 4. Mapeamos al formato [id => "Nombre Apellido (DNI: 123)"] que requiere el Select
        return $resultado->mapWithKeys(fn ($item) => [
            $item->id => "{$item->nombre} {$item->apellido} (DNI: {$item->dni})"
        ])->toArray();
    }
    public static function getPersonas(): array
    {
        $resultado = Persona::query()
        ->select('id', 'nombre', 'apellido', 'dni')
        ->get();

        return $resultado->mapWithKeys(fn ($item) => [
            $item->id => "{$item->nombre} {$item->apellido} (DNI: {$item->dni})"
        ])->toArray();
    }

}
