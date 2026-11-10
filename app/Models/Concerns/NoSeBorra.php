<?php

namespace App\Models\Concerns;

use LogicException;

/**
 * Requerimiento del sistema: no se borra ningún registro.
 * Cualquier intento de eliminación física (delete/forceDelete) se rechaza
 * a nivel de modelo, sin importar desde dónde se invoque (UI, API, tinker).
 * Para dar de baja se usa el estado del registro (p. ej. Legajo::estado).
 */
trait NoSeBorra
{
    public static function bootNoSeBorra(): void
    {
        static::deleting(function ($modelo): never {
            throw new LogicException(
                class_basename($modelo).': los registros no se pueden eliminar; use la baja o desadjunte el registro.'
            );
        });
    }
}
