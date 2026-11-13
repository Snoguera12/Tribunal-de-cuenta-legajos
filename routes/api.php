<?php
use App\Http\Controllers\Api\TokenController;
use App\Http\Controllers\Api\LegajoController;
use App\Http\Controllers\Api\LicenciaController;
use App\Http\Controllers\Api\PersonaController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/legajos/por-dni/{dni}', [LegajoController::class, 'show_dni']);
    Route::get('/personas', [PersonaController::class, 'index']);
    // Estado del token: llega solo si sigue vigente (revocado o vencido responde 401)
    Route::get('/token/estado', [TokenController::class, 'estado']);

    // Licencias (Fase 2): el sistema de licencias registra las licencias aprobadas
    Route::post('/legajos/{legajo}/licencias', [LicenciaController::class, 'store'])->whereNumber('legajo');
});
