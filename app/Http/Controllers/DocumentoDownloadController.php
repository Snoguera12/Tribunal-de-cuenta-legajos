<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\Documento;
use Illuminate\Support\Facades\Gate;
use App\Services\ArchivoSeguro;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\HeaderUtils;

class DocumentoDownloadController extends Controller
{
    // esto reemplaza el link directo a storage/, ahora hay que pasar por aca
    // para descargar un documento, y se chequea el permiso con la policy
    public function __invoke(Documento $documento): Response
    {
        Gate::authorize('view', $documento);

        Auditoria::registrar(
            evento: 'ver_documento',
            descripcion: 'Abrió el documento ' . ($documento->nombre_original ?: basename((string) $documento->ruta)),
            modelo: $documento,
        );

        abort_unless($documento->ruta && Storage::disk('local')->exists($documento->ruta), 404);

        // el archivo está cifrado: se descifra recién acá, en memoria (los documentos viejos sin cifrar también se abren)
        $contenido = ArchivoSeguro::leer($documento->ruta);

        if ($contenido === null) {
            Auditoria::registrar(
                evento: 'archivo_ilegible',
                descripcion: 'No se pudo descifrar el documento ' . basename($documento->ruta),
                modelo: $documento,
                extra: ['archivo' => $documento->ruta],
            );

            abort(404, 'No se pudo abrir el archivo.');
        }

        $nombre = $documento->nombre_original ?: basename($documento->ruta);

        return response($contenido, 200, [
            'Content-Type' => ArchivoSeguro::tipoMime($documento->ruta),
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_INLINE, $nombre, str_replace(['%', '"', '/', '\\'], '_', \Illuminate\Support\Str::ascii($nombre))),
            // el documento no puede ejecutar nada ni cargar nada, y no se guarda en el caché del navegador
            'Content-Security-Policy' => "default-src 'none'; sandbox",
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
