<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\Legajo;
use App\Models\Persona;
use App\Models\Token;
use Illuminate\Http\Request;

class LegajoController extends Controller
{
    public function show_dni(Request $request, $dni){
        $client = $request->user();
        if (!($client instanceof Token) || !$client->is_active) {
            Auditoria::registrar(
                evento: 'api_denegado',
                descripcion: 'Consulta de legajo por DNI rechazada',
                extra: ['dni' => $dni],
            );

            return response()->json(['error' => 'Acceso no autorizado o cliente inactivo.'], 403);
        }

        Auditoria::registrar(
            evento: 'api_consulta',
            descripcion: 'Consultó legajos por DNI',
            actor: $client,
            extra: ['dni' => $dni],
        );

        $persona = Persona::where('dni', $dni)->firstOrFail();

        if(!$persona){
            $data = [
                'message' => 'Persona no encontrado.',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $legajos = Legajo::where('persona_id', $persona->id)
        ->with(['categoria', 'cargo', 'area'])
        ->get();
        
        $data_legajo = $legajos->map(function ($legajo) {
            $baja = $legajo->isBaja() ? $legajo->historial->first() : null;
            $deshabilitacion = $legajo->isDeshabilitado() ? [
                'motivo' => $legajo->deshabilitado_motivo,
                'fecha' => $legajo->deshabilitado_en?->format('Y-m-d H:i:s'),
            ] : null;

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

                'deshabilitacion' => $deshabilitacion,
                
                'categoria' => [
                    'id' => $legajo->categoria->id,
                    'nombre' => $legajo->categoria->nombre,
                    'descripcion' => $legajo->categoria->descripcion,
                ],

                'cargo' => [
                    'id' => $legajo->cargo->id,
                    'nombre' => $legajo->cargo->nombre,
                ],

                'area' => [
                    'id' => $legajo->area->id,
                    'nombre' => $legajo->area->nombre,
                ],
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