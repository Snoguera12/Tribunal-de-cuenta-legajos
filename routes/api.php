<?php
use App\Http\Controllers\Api\DocumentoLicenciaController;
use App\Http\Controllers\Api\LegajoController;
use App\Http\Controllers\Api\LicenciaController;
use App\Http\Controllers\Api\PersonaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::get('/legajos/por-dni/{dni}', [LegajoController::class, 'show_dni'])->middleware('abilities:legajos:leer');
    Route::get('/personas', [PersonaController::class, 'index'])->middleware('abilities:personas:leer');

    // Licencias (Fase 2): el sistema de licencias registra las licencias aprobadas
    Route::post('/legajos/{legajo}/licencias', [LicenciaController::class, 'store'])->whereNumber('legajo')->middleware('abilities:licencias:crear');

    // PDF de la licencia aprobada: queda en documentos pendientes para que RRHH lo asigne
    Route::post('/licencias/documentos', [DocumentoLicenciaController::class, 'store'])->middleware('abilities:licencias:crear');
});
