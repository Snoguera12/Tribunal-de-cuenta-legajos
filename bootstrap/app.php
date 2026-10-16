<?php

use App\Http\Middleware\SecurityHeaders;
use App\Models\Auditoria;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);

        // detras de un proxy/balanceador, para que la IP de la auditoria sea la real
        // TRUSTED_PROXIES=* o una lista de IPs separadas por coma. Sin esto no se confia en ninguno
        if ($proxies = env('TRUSTED_PROXIES')) {
            $middleware->trustProxies(at: $proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // cada error 500 queda en la auditoria (sin datos de la peticion)
        $exceptions->report(function (\Throwable $e) {
            Auditoria::registrar(
                evento: 'error_sistema',
                descripcion: class_basename($e) . ': ' . mb_substr($e->getMessage(), 0, 200),
                extra: [
                    'archivo' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $e->getFile()),
                    'linea' => $e->getLine(),
                ],
            );
        });

    })->create();