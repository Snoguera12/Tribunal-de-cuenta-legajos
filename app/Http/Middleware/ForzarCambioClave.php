<?php

namespace App\Http\Middleware;

use App\Filament\Pages\CambiarClave;
use App\Models\User;
use Closure;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;


// mientras tenga la clave provisoria solo puede entrar a la pagina de cambio de clave
class ForzarCambioClave
{

    public function handle(Request $request, Closure $next): Response
    {
        $usuario = Filament::auth()->user();

        if ($usuario instanceof User
            && $usuario->debe_cambiar_clave
            && ! $request->hasHeader('X-Livewire')
            && ! $request->routeIs(CambiarClave::getRouteName(), 'filament.legajos.auth.logout')) {

            Notification::make()
                ->title('Cambiá tu clave')
                ->body('Tu clave es provisoria. Elegí una propia para poder usar el sistema.')
                ->warning()
                ->send();

            return redirect()->to(CambiarClave::getUrl());
        }

        return $next($request);
    }
}
