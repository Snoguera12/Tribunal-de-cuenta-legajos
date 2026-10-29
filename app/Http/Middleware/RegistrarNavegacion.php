<?php

namespace App\Http\Middleware;

use App\Models\Auditoria;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;


// anota cada pagina que abre un usuario dentro del panel
class RegistrarNavegacion
{

    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);

        try {

            if ($request->user() && $request->isMethod('GET') && ! $request->expectsJson() && ! $request->ajax()) {

                $codigo = $respuesta->getStatusCode();

                if ($codigo === 403) {
                    Auditoria::registrar(
                        evento: 'acceso_denegado',
                        descripcion: 'Intentó abrir una página sin permiso: /' . $request->path(),
                        extra: ['codigo' => $codigo],
                    );
                } elseif ($codigo < 400 && ! $respuesta->isRedirection()) {
                    Auditoria::registrar(
                        evento: 'navegacion',
                        descripcion: 'Abrió /' . $request->path(),
                        extra: ['ruta' => $request->route()?->getName()],
                    );
                }
            }

        } catch (\Throwable $e) {
            // la auditoria nunca tiene que tirar abajo la pagina
        }

        return $respuesta;
    }
}
