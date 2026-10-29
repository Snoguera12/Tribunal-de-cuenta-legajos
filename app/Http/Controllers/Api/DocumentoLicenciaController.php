<?php

namespace App\Http\Controllers\Api;

use App\Enums\TipodocEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentoLicenciaRequest;
use App\Models\ApiClient;
use App\Models\Auditoria;
use App\Models\Documento;
use App\Services\ArchivoSeguro;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;

class DocumentoLicenciaController extends Controller
{
    /**
     * POST /api/licencias/documentos   (multipart: archivo + id_solicitud, nro_resolucion, descripcion)
     *
     * Recibe el PDF de una licencia aprobada y lo deja en "Documentos pendientes".
     * RRHH lo asigna despues a una persona, a varias o a todas, sin copiar el archivo.
     *  - 201: documento nuevo, queda pendiente
     *  - 200: ya lo teníamos (mismo id_solicitud o mismo archivo): no se duplica
     *  - 422: datos inválidos
     */
    public function store(StoreDocumentoLicenciaRequest $request): JsonResponse
    {
        /** @var ApiClient $client */
        $client = $request->user();
        $idExterno = $request->filled('id_solicitud') ? (string) $request->input('id_solicitud') : null;

        // mismo numero de solicitud del mismo cliente: es un reintento
        if ($idExterno) {
            $existente = Documento::withTrashed()->where('api_client_id', $client->id)->where('id_externo', $idExterno)->first();

            if ($existente) {
                return $this->responder($existente, 'Ese documento ya estaba recibido', 200);
            }
        }

        $archivo = $request->file('archivo');
        $contenido = file_get_contents($archivo->getRealPath());

        if ($contenido === false) {
            return response()->json(['ok' => false, 'message' => 'No se pudo leer el archivo.'], 422);
        }

        $ruta = ArchivoSeguro::guardarContenido($contenido, $archivo->getClientOriginalName(), 'documentos');

        // el mismo PDF mandado con otro numero: se usa el que ya esta, no se repite
        $mismoArchivo = Documento::withTrashed()->where('ruta', $ruta)->where('origen', 'api_licencias')->first();

        if ($mismoArchivo) {
            return $this->responder($mismoArchivo, 'Ese archivo ya estaba recibido', 200);
        }

        $partes = array_filter([
            'Licencia aprobada',
            $request->filled('nro_resolucion') ? 'Res. ' . $request->input('nro_resolucion') : null,
            $idExterno ? 'solicitud ' . $idExterno : null,
        ]);

        try {
            $documento = Documento::create([
                'ruta' => $ruta,
                'nombre_original' => mb_substr($archivo->getClientOriginalName(), 0, 255),
                'descripcion' => $request->input('descripcion') ?: implode(' - ', $partes),
                'tipodoc' => TipodocEnum::Licencia->value,
                'legajo_id' => null,            // sin asignar: queda en documentos pendientes
                'origen' => 'api_licencias',
                'api_client_id' => $client->id,
                'id_externo' => $idExterno,
                'dni_sugerido' => $request->input('dni'),
            ]);
        } catch (UniqueConstraintViolationException) {
            // dos envios al mismo tiempo: el segundo es un reintento
            $documento = Documento::withTrashed()->where('api_client_id', $client->id)->where('id_externo', $idExterno)->firstOrFail();

            return $this->responder($documento, 'Ese documento ya estaba recibido', 200);
        }

        Auditoria::registrar(
            evento: 'documento_recibido_api',
            descripcion: 'Recibió por API el PDF de una licencia aprobada (queda pendiente de asignar)',
            modelo: $documento,
            extra: ['cliente' => $client->nombre, 'id_solicitud' => $idExterno],
        );

        return $this->responder($documento, 'Documento recibido: queda pendiente de asignar a uno o varios agentes', 201);
    }

    private function responder(Documento $documento, string $mensaje, int $codigo): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'id' => $documento->id,
            'mensaje' => $mensaje,
            'id_solicitud' => $documento->id_externo,
            'pendiente' => $documento->legajo_id === null,
        ], $codigo);
    }
}
