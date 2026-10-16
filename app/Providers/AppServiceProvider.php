<?php

namespace App\Providers;

<<<<<<< HEAD
use App\Policies\AuditoriaPolicy;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
=======
use App\Models\Auditoria;
use App\Services\ControlIntentosLogin;
use App\Services\SesionUnica;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
>>>>>>> version2-20261014_210855
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
<<<<<<< HEAD
        // hay que registrar la policy a mano porque Activity no es de App\Models
        Gate::policy(Activity::class, AuditoriaPolicy::class);

        // guarda en el log cuando alguien entra o sale (login/logout)
        Event::listen(Login::class, function (Login $event) {
            activity('auth')
                ->causedBy($event->user)
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => (string) request()->userAgent(),
                    'rol' => $event->user->rol ?? null,
                ])
                ->event('login')
                ->log('Inicio de sesión');
        });

        Event::listen(Logout::class, function (Logout $event) {
            if (!$event->user) {
                return;
            }

            activity('auth')
                ->causedBy($event->user)
                ->withProperties([
                    'ip' => request()->ip(),
                    'rol' => $event->user->rol ?? null,
                ])
                ->event('logout')
                ->log('Cierre de sesión');
        });
=======

        // despues de migrar se actualizan las tablas historicas
        Event::listen(MigrationsEnded::class, function () {
            try {
                Artisan::call('historico:sincronizar');
            } catch (\Throwable $e) {
                logger()->error('No se pudo sincronizar el histórico: ' . $e->getMessage());
            }
        });


        // ---- auditoria de sesiones

        Event::listen(Login::class, function (Login $evento) {

            if ($evento->user instanceof \App\Models\User) {

                // clave correcta: se borran los intentos fallidos
                ControlIntentosLogin::reiniciar($evento->user);

                // primer ingreso: se anota (la pantalla de cambio de clave la muestra el middleware)
                if (! $evento->user->primer_ingreso_en) {
                    \Illuminate\Support\Facades\DB::table('users')->where('id', $evento->user->id)->update(['primer_ingreso_en' => now()]);

                    Auditoria::registrar(
                        evento: 'primer_ingreso',
                        descripcion: "{$evento->user->name} ingresó por primera vez" . ($evento->user->debe_cambiar_clave ? ' (debe cambiar su clave provisoria)' : ''),
                        actor: $evento->user,
                    );
                }
            }

            Auditoria::registrar(
                evento: 'login',
                descripcion: 'Inició sesión',
                actor: $evento->user,
                extra: ['guard' => $evento->guard, 'recordarme' => $evento->remember ? 'si' : 'no'],
            );
        });


        Event::listen(Logout::class, function (Logout $evento) {

            // libera el lugar de la sesion unica (solo si la registrada es esta)
            if ($evento->user instanceof \App\Models\User && session()->getId()) {
                SesionUnica::liberar($evento->user, session()->getId());
            }

            Auditoria::registrar(
                evento: 'logout',
                descripcion: 'Cerró sesión',
                actor: $evento->user,
            );
        });


        Event::listen(Failed::class, function (Failed $evento) {

            // la cuenta existe pero esta deshabilitada (baja de legajo, por ejemplo)
            if ($evento->user instanceof \App\Models\User && ! $evento->user->isHabilitado()) {

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

            // la contraseña nunca se guarda, solo el correo que se intento usar
            Auditoria::registrar(
                evento: 'login_fallido',
                descripcion: 'Intento de inicio de sesión fallido',
                extra: [
                    'email' => $evento->credentials['email'] ?? null,
                    'usuario_existe' => $evento->user ? 'si' : 'no',
                ],
            );

            // cuenta los intentos y arma el mensaje que muestra la pagina de login
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


        // ---- auditoria de exportaciones e importaciones (las de filament)

        Export::created(function (Export $exportacion) {
            Auditoria::registrar(
                evento: 'exportacion',
                descripcion: 'Pidió una exportación de ' . class_basename($exportacion->exporter),
                extra: [
                    'exportador' => $exportacion->exporter,
                    'filas' => $exportacion->total_rows,
                ],
            );
        });


        Import::created(function (Import $importacion) {
            Auditoria::registrar(
                evento: 'importacion',
                descripcion: 'Pidió una importación de ' . class_basename($importacion->importer),
                extra: [
                    'importador' => $importacion->importer,
                    'archivo' => $importacion->file_name,
                    'filas' => $importacion->total_rows,
                ],
            );
        });

>>>>>>> version2-20261014_210855
    }
}
