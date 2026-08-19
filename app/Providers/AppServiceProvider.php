<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
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
        // en produccion forzamos https para que las cookies no viajen sin cifrar
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
