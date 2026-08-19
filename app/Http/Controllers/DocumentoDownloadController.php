<?php

namespace App\Http\Controllers;

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

        abort_unless(Storage::disk('local')->exists($documento->archivo), 404);

        return Storage::disk('local')->download(
            $documento->archivo,
            'documento-' . $documento->id . '.' . pathinfo($documento->archivo, PATHINFO_EXTENSION)
        );
    }
}
