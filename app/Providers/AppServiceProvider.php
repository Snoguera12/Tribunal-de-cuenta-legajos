<?php

namespace App\Providers;

use App\Policies\AuditoriaPolicy;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;

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
    }
}
