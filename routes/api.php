<?php

use App\Http\Controllers\Api\AreaController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\LegajoController;
use App\Models\ApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum:api')->group(function () {
    Route::get('/areas', [AreaController::class, 'index']);
    Route::get('/categorias', [CategoriaController::class, 'index']);
    Route::get('/legajos', [LegajoController::class, 'index']);

    Route::get('/datos-compartidos', function (Request $request) {
        // El "user()" de Sanctum ahora devolverá una instancia de tu modelo ApiClient
        $client = $request->user(); 

        // Validamos si el cliente no está usando el modelo User y si está activo
        if (!($client instanceof ApiClient) || !$client->is_active) {
            return response()->json(['error' => 'Acceso no autorizado o cliente inactivo.'], 403);
        }

        // Si todo está bien, retornas los datos que el tercero necesita
        return response()->json([
            'status' => 'success',
            'autorizado_para' => $client->name,
            'data' => [
                ['id' => 1, 'producto' => 'Stock A', 'cantidad' => 50],
                ['id' => 2, 'producto' => 'Stock B', 'cantidad' => 120],
            ]
        ]);
    });

});
