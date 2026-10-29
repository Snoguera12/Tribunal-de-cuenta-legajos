<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


/*
 * un usuario, una sesion. Se guarda una huella de la sesion activa en users.
 * Se escribe con query builder (no con el modelo) para no llenar la auditoria en cada pagina.
 */
class SesionUnica
{

    public static function modo(): string
    {
        return (string) config('sistema.sesion_unica.modo');
    }


    public static function activa(): bool
    {
        return in_array(self::modo(), ['bloquear', 'reemplazar'], true);
    }


    // no se guarda el id real de la sesion, solo un hash
    public static function huella(string $idSesion): string
    {
        return substr(hash('sha256', $idSesion), 0, 40);
    }


    private static function fila(User $usuario): ?object
    {
        return DB::table('users')->where('id', $usuario->id)
            ->first(['sesion_activa_id', 'sesion_activa_ip', 'ultima_actividad_en']);
    }


    // hay otra sesion (distinta a esta) que se uso hace poco
    public static function hayOtraActiva(User $usuario, string $huellaActual): ?object
    {
        $fila = self::fila($usuario);

        if (! $fila || ! $fila->sesion_activa_id || $fila->sesion_activa_id === $huellaActual || ! $fila->ultima_actividad_en) {
            return null;
        }

        $limite = now()->subMinutes(config('sistema.sesion_unica.inactividad_minutos'));

        return Carbon::parse($fila->ultima_actividad_en)->gt($limite) ? $fila : null;
    }


    public static function registrar(User $usuario, string $idSesion, ?string $ip = null): void
    {
        DB::table('users')->where('id', $usuario->id)->update([
            'sesion_activa_id' => self::huella($idSesion),
            'sesion_activa_ip' => $ip,
            'sesion_activa_desde' => now(),
            'ultima_actividad_en' => now(),
        ]);
    }


    // para no escribir en cada pedido: como mucho una vez cada 30 segundos
    public static function tocar(User $usuario): void
    {
        $ultima = $usuario->ultima_actividad_en;

        if (! $ultima || $ultima->lt(now()->subSeconds(30))) {
            DB::table('users')->where('id', $usuario->id)->update(['ultima_actividad_en' => now()]);
        }
    }


    // $idSesion: solo libera si la sesion registrada es esa (al cerrar sesion)
    public static function liberar(User $usuario, ?string $idSesion = null): void
    {
        $consulta = DB::table('users')->where('id', $usuario->id);

        if ($idSesion !== null) {
            $consulta->where('sesion_activa_id', self::huella($idSesion));
        }

        $consulta->update(['sesion_activa_id' => null, 'sesion_activa_ip' => null, 'sesion_activa_desde' => null]);
    }
}
