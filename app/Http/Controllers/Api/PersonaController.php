<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\Token;
use App\Models\Persona;
use Illuminate\Http\Request;

class PersonaController extends Controller
{
    public function index(Request $request){
        // El "user()" de Sanctum ahora devolverá una instancia de tu modelo Token
        $client = $request->user();

        // Validamos si el cliente no está usando el modelo User y si está activo
        if (!($client instanceof Token) || !$client->is_active) {
            Auditoria::registrar(
                evento: 'api_denegado',
                descripcion: 'Consulta del listado de personas rechazada',
            );

            return response()->json(['error' => 'Acceso no autorizado o cliente inactivo.'], 403);
        }

        Auditoria::registrar(
            evento: 'api_consulta',
            descripcion: 'Consultó el listado de personas',
            actor: $client,
        );

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
}
