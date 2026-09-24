<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiClient;
use App\Models\Legajo;
use App\Models\Persona;
use Illuminate\Http\Request;

class LegajoController extends Controller
{
    public function index(Request $request){
        // El "user()" de Sanctum ahora devolverá una instancia de tu modelo ApiClient
        $client = $request->user();

        // Validamos si el cliente no está usando el modelo User y si está activo
        if (!($client instanceof ApiClient) || !$client->is_active) {
            return response()->json(['error' => 'Acceso no autorizado o cliente inactivo.'], 403);
        }
        
        $legajos = Legajo::all();

        if($legajos->isEmpty()){
            $data = [
                'message' => 'No se encontraron Legajos.',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        return response()->json($legajos, 200);
    }

    public function show_id(Request $request, $id){
        $client = $request->user();
        if (!($client instanceof ApiClient) || !$client->is_active) {
            return response()->json(['error' => 'Acceso no autorizado o cliente inactivo.'], 403);
        }

        $legajo = Legajo::find($id);

        if(!$legajo){
            $data = [
                'message' => 'Legajo no encontrado por id.',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
        
        return response()->json($legajo, 200);
    }

    public function show_num_legajo(Request $request, $num_legajo){
        $client = $request->user();
        if (!($client instanceof ApiClient) || !$client->is_active) {
            return response()->json(['error' => 'Acceso no autorizado o cliente inactivo.'], 403);
        }

        $legajo = Legajo::find($num_legajo);

        if(!$legajo){
            $data = [
                'message' => 'Legajo no encontrado por número de legajo.',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
        
        return response()->json($legajo, 200);
    }

    public function show_dni(Request $request, $dni){
        $client = $request->user();
        if (!($client instanceof ApiClient) || !$client->is_active) {
            return response()->json(['error' => 'Acceso no autorizado o cliente inactivo.'], 403);
        }

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
            return $legajo ? [
                'id' => $legajo->id,
                'num_legajo' => $legajo->num_legajo,
                'estado' => $legajo->estado,
                'fecha_de_ingreso' => $legajo->fecha_de_ingreso,
                'tipo_contrato' => $legajo->tipo_contrato,

                'categoria' => [
                    'id' => $legajo->categoria->id,
                    'nombre' => $legajo->categoria->nombre,
                    'descripcion' => $legajo->categoria->descripcion,
                ],

                'cargo' =>[
                    'id' => $legajo->cargo->id,
                    'nombre' => $legajo->cargo->nombre,
                ],

                'area' =>[
                    'id' => $legajo->area->id,
                    'nombre' => $legajo->area->nombre,
                ]
            ] : null;
        });

        $data = [
            'persona' => [
                'id' => $persona->id,
                'nombre' => $persona->nombre,
                'dni' => $persona->dni,
                'cuil' => $persona->cuil,
                'email' => $persona->email
            ],
            'legajo' => $data_legajo
        ];
        
        return response()->json($data, 200);
    }
}