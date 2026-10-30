<?php

namespace App\Http\Controllers\Api;

use App\Enums\TipodocEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentoLicenciaRequest;
use App\Models\Documento;
use App\Models\Legajo;
use App\Models\Persona;
use App\Models\Token;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DocumentoLicenciaController extends Controller
{
    /**
     * POST /api/licencias/documentos
     *
     * Recibe el PDF de una licencia aprobada y lo guarda en el legajo de alta de la persona con ese DNI.
     * Idempotente por (cliente, id_solicitud):
     *  - 201: documento nuevo
     *  - 200: reenvío idéntico (mismo legajo y mismo archivo); no duplica
     *  - 409: mismo id_solicitud pero con otro legajo o con otro archivo
     *  - 422: datos inválidos, DNI sin persona o persona sin legajo de alta
     */
    public function store(StoreDocumentoLicenciaRequest $request): JsonResponse
    {
        /** @var Token $cliente */
        $cliente = $request->user();
        $datos = $request->validated();
        $idSolicitud = (int) $datos['id_solicitud'];
        $archivo = $request->file('archivo');
        $sha256 = hash_file('sha256', $archivo->getRealPath());

        $legajo = $this->legajoDeAltaPorDni($datos['dni']);
        if (! $legajo) {
            return response()->json([
                'message' => 'Los datos enviados no son válidos.',
                'errors' => ['dni' => ['No hay una persona con legajo de alta para ese DNI.']],
            ], 422);
        }

        $existente = $this->buscarExistente($cliente, $idSolicitud);
        if ($existente) {
            return $this->responderExistente($existente, $legajo, $sha256);
        }

        try {
            $documento = DB::transaction(function () use ($cliente, $datos, $idSolicitud, $archivo, $sha256, $legajo) {
                $documento = Documento::create([
                    'ruta' => $archivo->store('documentos', 'local'),
                    'nombre_original' => $archivo->getClientOriginalName(),
                    'descripcion' => $datos['descripcion'] ?? 'Licencia aprobada' . (isset($datos['nro_resolucion']) ? " (resolución {$datos['nro_resolucion']})" : ''),
                    'tipodoc' => TipodocEnum::Licencia,
                    'legajo_id' => $legajo->id,
                    'token_id' => $cliente->id,
                    'id_solicitud' => $idSolicitud,
                    'sha256' => $sha256,
                ]);

                $documento->legajos()->syncWithoutDetaching([$legajo->id]);

                return $documento;
            });
        } catch (UniqueConstraintViolationException) {
            // Dos envíos simultáneos del mismo id_solicitud: el segundo se trata como reintento
            $existente = $this->buscarExistente($cliente, $idSolicitud);
            if (! $existente) {
                throw new \RuntimeException('No se pudo guardar el documento de la licencia.');
            }

            return $this->responderExistente($existente, $legajo, $sha256);
        }

        return $this->respuesta($documento, $legajo, 'Documento de licencia registrado en el legajo', 201);
    }

    private function legajoDeAltaPorDni(string $dni): ?Legajo
    {
        // Persona::legajos() ya filtra solo los legajos de alta
        return Persona::where('dni', $dni)->first()?->legajos()->first();
    }

    private function buscarExistente(Token $cliente, int $idSolicitud): ?Documento
    {
        return Documento::query()
            ->where('token_id', $cliente->id)
            ->where('id_solicitud', $idSolicitud)
            ->first();
    }

    private function responderExistente(Documento $existente, Legajo $legajo, string $sha256): JsonResponse
    {
        $mismoLegajo = (int) $existente->legajo_id === (int) $legajo->id;
        $mismoArchivo = $existente->sha256 === $sha256;

        if (! $mismoLegajo || ! $mismoArchivo) {
            return response()->json([
                'message' => 'Ya existe un documento con ese id_solicitud pero con datos distintos.',
            ], 409);
        }

        return $this->respuesta($existente, $legajo, 'Documento de licencia ya registrado', 200);
    }

    private function respuesta(Documento $documento, Legajo $legajo, string $mensaje, int $estado): JsonResponse
    {
        return response()->json([
            'message' => $mensaje,
            'documento_id' => $documento->id,
            'legajo_id' => $legajo->id,
            'id_solicitud' => $documento->id_solicitud,
        ], $estado);
    }
}
