<?php

namespace App\Models\Concerns;

use App\Models\ActividadReciente;

// al guardar el modelo anota que la persona de RRHH logueada modifico ese agente o usuario
// el modelo dice a quien corresponde en actividadReciente(): [tipo, id] o null si no cuenta
trait RegistraActividadReciente
{
    public static function bootRegistraActividadReciente(): void
    {
        static::created(fn ($modelo) => self::anotarActividad($modelo, true));
        static::updated(fn ($modelo) => self::anotarActividad($modelo, false));
    }

    private static function anotarActividad($modelo, bool $creado): void
    {
        $dato = $modelo->actividadReciente($creado);

        if ($dato) {
            ActividadReciente::registrar($dato[0], (int) $dato[1], 'modificado');
        }
    }
}
