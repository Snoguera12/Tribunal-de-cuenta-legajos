<?php

use App\Http\Middleware\SecurityHeaders;
use App\Models\Auditoria;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e): bool {
            return $request->is('api/*') || $request->expectsJson();
        });

        // cada error 500 queda en la auditoría (sin datos de la petición); varios seguidos abren un incidente
        $exceptions->report(function (Throwable $e) {
            Auditoria::registrar(
                evento: 'error_sistema',
                descripcion: class_basename($e) . ': ' . mb_substr($e->getMessage(), 0, 200),
                extra: [
                    'archivo' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $e->getFile()),
                    'linea' => $e->getLine(),
                ],
            );
        });

        // 404 de la API (legajo inexistente, ruta inexistente) sin detalles internos
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Recurso no encontrado.'], 404);
            }
        });
    })->create();