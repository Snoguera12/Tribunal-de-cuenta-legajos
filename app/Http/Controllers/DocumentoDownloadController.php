<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\Documento;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentoDownloadController extends Controller
{
    // esto reemplaza el link directo a storage/, ahora hay que pasar por aca
    // para descargar un documento, y se chequea el permiso con la policy
    public function __invoke(Documento $documento): StreamedResponse
    {
        Gate::authorize('view', $documento);

        Auditoria::registrar(
            evento: 'ver_documento',
            descripcion: 'Abrió el documento ' . ($documento->nombre_original ?: basename((string) $documento->ruta)),
            modelo: $documento,
        );

        abort_unless($documento->ruta && Storage::disk('local')->exists($documento->ruta), 404);

        return Storage::disk('local')->response(
            $documento->ruta,
            $documento->nombre_original ?: basename($documento->ruta)
        );
    }
}
