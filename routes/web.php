<?php

use App\Models\Auditoria;
use App\Models\Documento;
use App\Services\ArchivoSeguro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', fn () => redirect('legajos'));

Route::get('/login', fn () => redirect()->to('/legajos/login'))->name('login');


// documentos (link firmado). Solo matchea rutas que empiezan con documentos/
Route::get('/{path}', function (Request $request, string $path) {

    if (! auth()->check()) {
        abort(401);
    }

    if (! $request->hasValidSignature()) {
        abort(403, 'El enlace ha expirado.');
    }

    if (str_contains($path, '..')) {
        abort(404);
    }

    $documento = Documento::withTrashed()->where('ruta', $path)->first();

    $descargar = $request->boolean('descargar');

    // el empleado ve que existe pero no lo abre (salvo que se active DOC_EMPLEADO_ABRE_PROPIOS y sea suyo)
    if (! Documento::puedeAbrirContenido(auth()->user(), $documento)) {
        Auditoria::registrar(
            evento: 'acceso_denegado',
            descripcion: 'Un empleado intentó ' . ($descargar ? 'descargar' : 'abrir') . ' un documento',
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
        evento: $descargar ? 'descargar_documento' : 'ver_documento',
        descripcion: ($descargar ? 'Descargó' : 'Abrió') . ' el documento ' . basename($path),
        modelo: $documento,
        extra: ['archivo' => $path, 'legajo_id' => $documento?->legajo_id],
    );

    // al descargar se guarda con un nombre claro (el original si se conoce); al abrir se muestra en el navegador
    $nombre = $documento ? $documento->nombreDescarga() : basename($path);

    return response($contenido, 200, [
        'Content-Type' => ArchivoSeguro::tipoMime($path),
        'Content-Disposition' => ($descargar ? 'attachment' : 'inline') . '; filename="' . $nombre . '"',
        // el documento no puede ejecutar nada ni cargar nada, y no se guarda en cache
        'Content-Security-Policy' => "default-src 'none'; sandbox",
        'Cache-Control' => 'private, no-store',
    ]);
})
->where('path', 'documentos/.*')
->name('documentos.ver');
