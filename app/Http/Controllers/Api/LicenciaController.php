<?php

namespace App\Http\Controllers\Api;

use App\Enums\TipodocEnum;
use App\Filament\Forms\Components\ArchivoDocumento;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLicenciaRequest;
use App\Models\Documento;
use App\Models\Legajo;
use App\Models\Licencia;
use App\Models\Token;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class LicenciaController extends Controller
{
    /**
     * POST /api/legajos/{legajo}/licencias
     *
     * Registra la licencia aprobada y guarda su PDF de respaldo en el legajo.
     * Todo en una sola operación. Idempotente por (cliente, id_solicitud):
     *  - 201: licencia y documento nuevos
     *  - 200: reenvío idéntico (mismo legajo, datos y archivo); no duplica
     *  - 409: mismo solicitud_id con otro legajo, otros datos o otro archivo
     *  - 422: datos inválidos o archivo inválido
     */
    public function store(StoreLicenciaRequest $request, Legajo $legajo): JsonResponse
    {
        /** @var Token $client */
        $datos = Arr::except($request->validated(), ['archivo']);
        $archivo = $request->file('archivo');
        $solicitud_id = (int) $datos['solicitud_id'];

        $existente = $this->buscarExistente($solicitud_id);
        if ($existente) {
            return $this->responderReenvio($existente, $legajo, $datos);
        }

        // El archivo se guarda antes de la transacción; si algo falla, se borra
        $ruta = $archivo->store(ArchivoDocumento::DIRECTORIO, ArchivoDocumento::DISCO);

        try {
            $licencia = DB::transaction(function () use ($legajo, $datos, $archivo, $ruta) {

                $documento = Documento::create([
                    'ruta' => $ruta,
                    'nombre_original' => $archivo->getClientOriginalName(),
                    'descripcion' => 'Licencia aprobada' . (isset($datos['nro_resolucion']) ? " (resolución {$datos['nro_resolucion']})" : ''),
                    'tipodoc' => TipodocEnum::Licencia,
                ]);

                $licencia = Licencia::create([
                    ...$datos,
                    'documento_id' => $documento->id,
                ]);

                $documento->legajos()->syncWithoutDetaching([$legajo->id]);

                return $licencia;
            });
        } catch (UniqueConstraintViolationException) {
            // Dos envíos simultáneos del mismo id_solicitud: el segundo se trata como reintento
            Storage::disk(ArchivoDocumento::DISCO)->delete($ruta);

            $existente = $this->buscarExistente($solicitud_id);
            if (! $existente) {
                throw new \RuntimeException('No se pudo guardar la licencia.');
            }

            return $this->responderReenvio($existente, $legajo, $datos);
        } catch (Throwable $e) {
            Storage::disk(ArchivoDocumento::DISCO)->delete($ruta);
            throw $e;
        }

        return $this->respuesta($licencia, 'Licencia y documento registrados en el legajo', 201);
    }

    private function buscarExistente(int $solicitudId): ?Licencia
    {
        return Licencia::query()
            ->where('solicitud_id', $solicitudId)
            ->first();
    }

    private function responderReenvio(Licencia $existente, Legajo $legajo, array $datos): JsonResponse
    {
        $mismoLegajo = $existente->documento?->legajos()->whereKey($legajo->id)->exists() ?? false;
        $mismosDatos = $existente->tieneLosMismosDatos($datos);

        if (!$mismoLegajo || !$mismosDatos) {
            return response()->json([
                'ok' => false,
                'message' => 'Ya existe una licencia con ese solicitud_id pero con datos o archivo distintos.',
                'id' => $existente->id,
                'solicitud_id' => $existente->solicitud_id,
                'documento_id' => $existente->documento_id,
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
            'id_solicitud' => $licencia->solicitud_id,
            'documento_id' => $licencia->documento_id,
        ], $codigo);
    }
}