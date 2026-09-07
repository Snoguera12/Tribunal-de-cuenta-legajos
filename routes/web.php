<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

Route::get('/', function () {
    return redirect('legajos');
});

Route::get('/login', function () {
    return redirect()->to('/legajos/login'); // O la ruta de tu panel
})->name('login');

Route::get('/documentos-revisar/{path}', function (string $path) {
    if (!auth()->check()) {
        abort(401, 'No autenticado.');
    }

    if (!Storage::disk('local')->exists($path)) {
        abort(404);
    }

    // Obtiene la ruta física real del archivo privado de forma segura
    $realPath = Storage::disk('local')->path($path);

    // Retorna el archivo directamente (el navegador intentará renderizarlo si es PDF/imagen)
    return Response::file($realPath);
})
->where('path', '.*')
->middleware(['signed', 'rol:rrhh, administrador'])
->name('documentos_revisar.ver');
