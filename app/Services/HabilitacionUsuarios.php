<?php

namespace App\Services;

use App\Models\Auditoria;
use App\Models\Legajo;
use App\Models\Persona;
use App\Models\User;


// sin legajos de alta -> usuario deshabilitado. Si vuelve a tener uno -> se habilita (solo si lo deshabilito el sistema)
class HabilitacionUsuarios
{

    // $forzar: la persona perdio un legajo (se lo pasaron a otra), aunque no le quede ninguno
    public static function sincronizar(?Persona $persona, bool $forzar = false): void
    {
        // se consulta de nuevo para no usar un usuario viejo guardado en la relacion
        $usuario = $persona?->usuario()->first();

        if (! $usuario) {
            return;
        }

        // si nunca tuvo legajos no se toca nada (los borrados cuentan como que tuvo)
        $tuvoLegajos = Legajo::withTrashed()->where('persona_id', $persona->id)->exists();

        if (! $tuvoLegajos && ! $forzar) {
            return;
        }

        $hayActivo = Legajo::where('persona_id', $persona->id)->get()
            ->contains(fn (Legajo $legajo) => $legajo->isAlta());

        if (! $hayActivo) {
            self::deshabilitar($usuario, 'baja_legajo');
        } else {
            self::habilitar($usuario, ['baja_legajo']);
        }
    }


    public static function deshabilitar(User $usuario, string $motivo): void
    {
        if (! $usuario->isHabilitado()) {
            return;
        }

        // el ultimo administrador no se puede dejar afuera sin querer
        if ($usuario->isAdmin()) {
            Auditoria::registrar(
                evento: 'usuario_no_deshabilitado',
                descripcion: "No se deshabilitó al administrador {$usuario->name} aunque su persona quedó de baja",
                modelo: $usuario,
                extra: ['motivo' => $motivo],
            );

            return;
        }

        $usuario->forceFill(['habilitado' => false, 'deshabilitado_motivo' => $motivo])->save();
    }


    // solo habilita si lo deshabilito el sistema por alguno de estos motivos
    public static function habilitar(User $usuario, array $motivos): void
    {
        if (! $usuario->isHabilitado() && in_array($usuario->deshabilitado_motivo, $motivos, true)) {
            $usuario->forceFill(['habilitado' => true])->save();
        }
    }
}
