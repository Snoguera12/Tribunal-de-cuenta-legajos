<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiClient;
use App\Models\Auditoria;
use App\Models\Persona;
use Illuminate\Http\Request;

class PersonaController extends Controller
{
    // ?por_pagina=50 (maximo 100) y ?page=2
    public function index(Request $request)
    {
        $client = $request->user();

        if (!($client instanceof ApiClient) || !$client->is_active) {
            Auditoria::registrar(
                evento: 'api_denegado',
                descripcion: 'Listado de personas rechazado',
            );

            return response()->json(['error' => 'Acceso no autorizado o cliente inactivo.'], 403);
        }

        $porPagina = min(max((int) $request->query('por_pagina', 50), 1), 100);

        $personas = Persona::orderBy('id')->paginate($porPagina, ['*'], 'page', max($request->integer('page', 1), 1));

        Auditoria::registrar(
            evento: 'api_consulta',
            descripcion: 'Listó personas (pagina ' . $personas->currentPage() . ')',
            extra: ['cantidad' => count($personas->items()), 'total' => $personas->total()],
        );

        if ($personas->total() === 0) {
            return response()->json(['message' => 'No se encontraron Personas.', 'status' => 404], 404);
        }

        return response()->json([
            'is_active' => $client->is_active,
            'pagina' => $personas->currentPage(),
            'por_pagina' => $personas->perPage(),
            'total' => $personas->total(),
            'ultima_pagina' => $personas->lastPage(),
            'personas' => $personas->items(),
        ], 200);
    }
}
