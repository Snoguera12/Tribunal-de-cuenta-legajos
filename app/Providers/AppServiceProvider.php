<?php

namespace App\Providers;

use App\Models\Auditoria;
use App\Models\User;
use App\Services\ControlIntentosLogin;
use App\Services\Etiquetas;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Etiquetas::registrar();

        // ---- auditoría de sesiones e intentos de clave

        Event::listen(Login::class, function (Login $evento) {
            // clave correcta: se borran los intentos fallidos
            if ($evento->user instanceof User) {
                ControlIntentosLogin::reiniciar($evento->user);
            }

            Auditoria::registrar(
                evento: 'login',
                descripcion: 'Inició sesión',
                actor: $evento->user,
                extra: ['guard' => $evento->guard, 'recordarme' => $evento->remember ? 'si' : 'no'],
            );
        });

        Event::listen(Logout::class, function (Logout $evento) {
            Auditoria::registrar(
                evento: 'logout',
                descripcion: 'Cerró sesión',
                actor: $evento->user,
            );
        });

        Event::listen(Failed::class, function (Failed $evento) {
            // la cuenta existe pero está deshabilitada
            if ($evento->user instanceof User && ! $evento->user->isHabilitado()) {
                // con un usuario deshabilitado filament dispara este evento dos veces: se anota una
                if (request()->attributes->has('deshabilitado_anotado')) {
                    return;
                }

                request()->attributes->set('deshabilitado_anotado', true);

                Auditoria::registrar(
                    evento: 'acceso_denegado',
                    descripcion: 'Un usuario deshabilitado intentó ingresar',
                    actor: $evento->user,
                    extra: ['motivo' => $evento->user->deshabilitado_motivo],
                );

                return;
            }

            // la contraseña nunca se guarda, solo el correo que se intentó usar
            Auditoria::registrar(
                evento: 'login_fallido',
                descripcion: 'Intento de inicio de sesión fallido',
                extra: [
                    'email' => $evento->credentials['email'] ?? null,
                    'usuario_existe' => $evento->user ? 'si' : 'no',
                ],
            );

            // cuenta los intentos y arma el mensaje que muestra la página de login
            if (! empty($evento->credentials['email'])) {
                request()->attributes->set('login_mensaje', ControlIntentosLogin::registrarFallo($evento->credentials['email']));
            }
        });

        Event::listen(PasswordReset::class, function (PasswordReset $evento) {
            Auditoria::registrar(
                evento: 'password_reset',
                descripcion: 'Restableció su contraseña',
                actor: $evento->user,
            );
        });
    }
}
