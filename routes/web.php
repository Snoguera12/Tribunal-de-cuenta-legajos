<?php
<<<<<<< HEAD
=======

use App\Models\Auditoria;
use App\Models\Documento;
use App\Services\ArchivoSeguro;
>>>>>>> version2-20261014_210855
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return redirect('legajos');
});

Route::get('/login', function () {
    return redirect()->to('/legajos/login');
})->name('login');


// documentos (link firmado). Solo matchea rutas que empiezan con documentos/
Route::get('/{path}', function (Request $request, string $path) {

    if (! auth()->check()) {
        abort(401);
    }

<<<<<<< HEAD
    $usuario = auth()->user();

    // el empleado ve que existe el doc pero no lo puede bajar
    if ($usuario->isEmpleado()) {
        abort(403, 'Tu rol no tiene permitido descargar documentos. Contactá a RRHH.');
=======
    if (! $request->hasValidSignature()) {
        abort(403, 'El enlace ha expirado.');
>>>>>>> version2-20261014_210855
    }

    if (str_contains($path, '..')) {
        abort(404);
    }

    $documento = Documento::withTrashed()->where('ruta', $path)->first();

    // el empleado ve que existe pero no lo abre
    if (auth()->user()->isEmpleado()) {
        Auditoria::registrar(
            evento: 'acceso_denegado',
            descripcion: 'Un empleado intentó abrir un documento',
            modelo: $documento,
            extra: ['archivo' => $path],
        );

        abort(403, 'Tu rol no tiene permitido descargar documentos. Contactá a RRHH.');
    }

    if (! Storage::disk('local')->exists($path)) {
        abort(404);
    }

    // el archivo esta cifrado, se desencripta antes de mostrarlo
    $contenido = ArchivoSeguro::leer($path);

    if ($contenido === null) {
        Auditoria::registrar(
            evento: 'archivo_ilegible',
            descripcion: 'No se pudo desencriptar el documento ' . basename($path),
            modelo: $documento,
            extra: ['archivo' => $path],
        );

        abort(404, 'No se pudo abrir el archivo.');
    }

    Auditoria::registrar(
        evento: 'ver_documento',
        descripcion: 'Abrió el documento ' . basename($path),
        modelo: $documento,
        extra: ['archivo' => $path, 'legajo_id' => $documento?->legajo_id],
    );

    return response($contenido, 200, [
        'Content-Type' => ArchivoSeguro::tipoMime($path),
        'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        // el documento no puede ejecutar nada ni cargar nada, y no se guarda en cache
        'Content-Security-Policy' => "default-src 'none'; sandbox",
        'Cache-Control' => 'private, no-store',
    ]);
})
->where('path', 'documentos/.*')
->name('documentos.ver');
