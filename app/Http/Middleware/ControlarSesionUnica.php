<?php

namespace App\Http\Middleware;

use App\Models\Auditoria;
use App\Models\User;
use App\Services\SesionUnica;
use Closure;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;


// si otra sesion ocupo el lugar de esta, esta se cierra
class ControlarSesionUnica
{

    public function handle(Request $request, Closure $next): Response
    {
        $usuario = Filament::auth()->user();

        if (! $usuario instanceof User || ! SesionUnica::activa() || ! $request->hasSession()) {
            return $next($request);
        }

        $idSesion = $request->session()->getId();

        if ($usuario->sesion_activa_id === null) {

            // sesion que ya estaba abierta antes de activar la regla: queda como la activa
            SesionUnica::registrar($usuario, $idSesion, $request->ip());

        } elseif (! hash_equals($usuario->sesion_activa_id, SesionUnica::huella($idSesion))) {

            Auditoria::registrar(
                evento: 'sesion_desplazada',
                descripcion: "Se cerró una sesión de {$usuario->name} porque ingresó desde otro lugar",
                actor: $usuario,
                extra: ['ip_sesion_nueva' => $usuario->sesion_activa_ip],
            );

            Filament::auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // livewire no sigue redirecciones: 419 le hace recargar la pagina
            if ($request->hasHeader('X-Livewire')) {
                abort(419);
            }

            Notification::make()
                ->title('Tu sesión se cerró')
                ->body('Se inició sesión con tu usuario desde otro equipo o navegador.')
                ->warning()
                ->send();

            return redirect()->to(Filament::getLoginUrl());

        } else {

            SesionUnica::tocar($usuario);
        }

        return $next($request);
    }
}
