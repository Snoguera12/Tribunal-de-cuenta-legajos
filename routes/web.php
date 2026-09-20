<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

Route::get('/', function () {
    return redirect('legajos');
});

Route::get('/login', function () {
    return redirect()->to('/legajos/login');
})->name('login');

// Dirección de documentos
Route::get('/{path}', function (Request $request, string $path) {
    if (!auth()->check()) {
        abort(401); // Usuario no autorizado.
    }
    
    if (!$request->hasValidSignature()) {
        abort(403, 'El enlace sea ha expirado.');
    }

    $usuario = auth()->user();

    // el empleado ve que existe el doc pero no lo puede bajar
    if ($usuario->isEmpleado()) {
        abort(403, 'Tu rol no tiene permitido descargar documentos. Contactá a RRHH.');
    }

    if (!Storage::disk('local')->exists($path)) {
        abort(404);
    }

    // Obtiene la ruta física real del archivo privado de forma segura
    $realPath = Storage::disk('local')->path($path);

    // Retorna el archivo directamente (el navegador intentará renderizarlo si es PDF/imagen)
    return Response::file($realPath);
})
->where('path', '.*',)
->name('documentos.ver');
