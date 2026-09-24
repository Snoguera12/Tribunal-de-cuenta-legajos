<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiClient;
use App\Models\Persona;
use Illuminate\Http\Request;

class PersonaController extends Controller
{
    public function index(Request $request){
        // El "user()" de Sanctum ahora devolverá una instancia de tu modelo ApiClient
        $client = $request->user();

        // Validamos si el cliente no está usando el modelo User y si está activo
        if (!($client instanceof ApiClient) || !$client->is_active) {
            return response()->json(['error' => 'Acceso no autorizado o cliente inactivo.'], 403);
        }

        $personas = Persona::all();

        if($personas->isEmpty()){
            $data = [
                'message' => 'No se encontraron Personas.',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
        $data = [
            'is_active' => !$request->user()->is_active,
            'personas' => $personas
        ];
        return response()->json($data, 200);
    }

    public function show_id(Persona $persona){

        if(!$persona){
            $data = [
                'message' => 'Persona no encontrado por ID.',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        return response()->json(['data' => $persona], 200);
    }

    public function show_dni(Persona $persona){

        if(!$persona){
            $data = [
                'message' => 'Persona no encontrado por DNI.',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        return response()->json(['data' => $persona], 200);
    }
}
