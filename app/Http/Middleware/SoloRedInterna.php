<?php

namespace App\Http\Middleware;

use App\Models\Auditoria;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;


// segunda barrera (la primera es Apache): solo deja pasar las IP de la red interna del Tribunal
class SoloRedInterna
{

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('sistema.red_interna.activa') || self::permitida((string) $request->ip())) {
            return $next($request);
        }

        $ip = (string) $request->ip();

        // un escaneo desde afuera no tiene que llenar la auditoria: se anota una vez cada 10 minutos por IP
        if (Cache::add('red_externa:' . $ip, 1, now()->addMinutes(10))) {
            Auditoria::registrar(
                evento: 'acceso_denegado',
                descripcion: "Pedido desde fuera de la red interna ({$ip})",
                extra: ['motivo' => 'red_externa', 'ruta' => $request->path()],
            );
        }

        abort(403, 'Este sistema solo se puede usar desde la red interna del Tribunal.');
    }


    // la propia máquina siempre pasa; el resto tiene que estar en la lista (IP sueltas o rangos tipo 10.0.0.0/8)
    public static function permitida(string $ip): bool
    {
        if ($ip === '' ) {
            return false;
        }

        $lista = array_merge(['127.0.0.1', '::1'], config('sistema.red_interna.permitidas', []));

        return IpUtils::checkIp($ip, $lista);
    }
}
