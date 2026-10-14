<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLicenciaRequest;
use App\Models\ApiClient;
use App\Models\Legajo;
use App\Models\Licencia;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;

class LicenciaController extends Controller
{
    /**
     * POST /api/legajos/{legajo}/licencias
     *
     * Registra en el legajo una licencia ya aprobada por el Sistema de Licencias.
     * Es idempotente por (cliente, id_solicitud):
     *  - 201: licencia nueva
     *  - 200: reintento idéntico (devuelve la ya registrada, no duplica)
     *  - 409: mismo id_solicitud pero con datos distintos
     *  - 422: datos inválidos
     */
    public function store(StoreLicenciaRequest $request, Legajo $legajo): JsonResponse
    {
        /** @var ApiClient $client */
        $client = $request->user();
        $datos = $request->validated();
        $idSolicitud = (int) $datos['id_solicitud'];

        $existente = $this->buscarExistente($client, $idSolicitud);
        if ($existente) {
            return $this->responderReenvio($existente, $legajo, $datos);
        }

        try {
            $licencia = Licencia::create([
                ...$datos,
                'legajo_id' => $legajo->id,
                'api_client_id' => $client->id,
                // Lo recibido tal cual (antes de normalizar), para auditoría
                'payload_original' => json_decode($request->getContent(), true) ?? $request->all(),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Dos envíos simultáneos del mismo id_solicitud: el segundo se trata como reintento.
            $existente = $this->buscarExistente($client, $idSolicitud);
            if (! $existente) {
                throw new \RuntimeException('No se pudo guardar la licencia.');
            }

            return $this->responderReenvio($existente, $legajo, $datos);
        }

        return $this->respuesta($licencia, 'Licencia registrada en el legajo', 201);
    }

    private function buscarExistente(ApiClient $client, int $idSolicitud): ?Licencia
    {
        return Licencia::query()
            ->where('api_client_id', $client->id)
            ->where('id_solicitud', $idSolicitud)
            ->first();
    }

    private function responderReenvio(Licencia $existente, Legajo $legajo, array $datos): JsonResponse
    {
        $mismoLegajo = (int) $existente->legajo_id === (int) $legajo->id;

        if (! $mismoLegajo || ! $existente->tieneLosMismosDatos($datos)) {
            return response()->json([
                'ok' => false,
                'message' => 'Ya existe una licencia con ese id_solicitud pero con datos distintos.',
                'id' => $existente->id,
                'id_solicitud' => $existente->id_solicitud,
                'legajo_id' => $existente->legajo_id,
            ], 409);
        }

        return $this->respuesta($existente, 'La licencia ya estaba registrada en el legajo', 200);
    }

    private function respuesta(Licencia $licencia, string $mensaje, int $codigo): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'id' => $licencia->id,
            'mensaje' => $mensaje,
            'id_solicitud' => $licencia->id_solicitud,
            'legajo_id' => $licencia->legajo_id,
        ], $codigo);
    }
}
