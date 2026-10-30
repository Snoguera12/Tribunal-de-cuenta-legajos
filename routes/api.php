<?php
use App\Http\Controllers\Api\DocumentoLicenciaController;
use App\Http\Controllers\Api\LegajoController;
use App\Http\Controllers\Api\LicenciaController;
use App\Http\Controllers\Api\PersonaController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/legajos/por-dni/{dni}', [LegajoController::class, 'show_dni']);
    Route::get('/personas', [PersonaController::class, 'index']);

    // Licencias (Fase 2): el sistema de licencias registra las licencias aprobadas
    Route::post('/legajos/{legajo}/licencias', [LicenciaController::class, 'store'])->whereNumber('legajo');

    // PDF de la licencia aprobada (multipart: archivo + datos), se guarda en el legajo de alta del DNI
    Route::post('/licencias/documentos', [DocumentoLicenciaController::class, 'store']);
});
