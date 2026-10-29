<?php

namespace App\Http\Controllers\Api;

use App\Enums\TipoContratoEnum;
use App\Http\Controllers\Controller;
use App\Models\ApiClient;
use App\Models\Auditoria;
use App\Models\Historialbaja;
use App\Models\Legajo;
use App\Models\Persona;
use Illuminate\Http\Request;

class LegajoController extends Controller
{
    public function show_dni(Request $request, $dni){
        $client = $request->user();
        if (!($client instanceof ApiClient) || !$client->is_active) {
            Auditoria::registrar(
                evento: 'api_denegado',
                descripcion: 'Consulta de legajo por DNI rechazada',
                extra: ['dni' => $dni],
            );

            return response()->json(['error' => 'Acceso no autorizado o cliente inactivo.'], 403);
        }

        // el dni tiene que ser solo numeros
        if (! ctype_digit((string) $dni) || strlen((string) $dni) > 12) {
            return response()->json(['message' => 'DNI inválido.', 'status' => 422], 422);
        }

        $persona = Persona::where('dni', $dni)->first();

        Auditoria::registrar(
            evento: 'api_consulta',
            descripcion: 'Consultó legajos por DNI',
            extra: ['dni' => $dni, 'resultado' => $persona ? 'encontrada' : 'sin resultados'],
        );

        if (! $persona) {
            return response()->json(['message' => 'Persona no encontrada.', 'status' => 404], 404);
        }

        $legajos = Legajo::where('persona_id', $persona->id)
        ->with(['categoria', 'cargo', 'area', 'historial'])
        ->get();
        
        $data_legajo = $legajos->map(function ($legajo) {
            $baja = ! $legajo->isAlta() ? $legajo->historial->sortByDesc('fecha_baja')->first() : null;

            return $legajo ? [
                'id' => $legajo->id,
                'num_legajo' => $legajo->num_legajo,
                'estado' => $legajo->estado,
                'fecha_de_ingreso' => $legajo->fecha_de_ingreso,
                'tipo_contrato' => $legajo->tipo_contrato?->getLabel(),

                'baja' => $baja ? [
                    'motivo' => $baja->motivo?->getLabel(),
                    'fecha_baja' => $baja->fecha_baja,
                ] : null,

                'categoria' => $legajo->categoria ? [
                    'id' => $legajo->categoria->id,
                    'nombre' => $legajo->categoria->nombre,
                    'descripcion' => $legajo->categoria->descripcion,
                ] : null,

                'cargo' => $legajo->cargo ? [
                    'id' => $legajo->cargo->id,
                    'nombre' => $legajo->cargo->nombre,
                ] : null,

                'area' => $legajo->area ? [
                    'id' => $legajo->area->id,
                    'nombre' => $legajo->area->nombre,
                ] : null,
            ] : null;
        });

        $data = [
            'persona' => [
                'id' => $persona->id,
                'nombre' => $persona->nombre,
                'apellido' => $persona->apellido,
                'dni' => $persona->dni,
                'cuil' => $persona->cuil,
                'email' => $persona->email
            ],
            'legajo' => $data_legajo
        ];
        
        return response()->json($data, 200);
    }
}