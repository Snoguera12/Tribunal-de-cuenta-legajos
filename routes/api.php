<?php
use App\Http\Controllers\Api\LegajoController;
use App\Http\Controllers\Api\PersonaController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/legajos/por-dni/{dni}', [LegajoController::class, 'show_dni']);
    Route::get('/personas', [PersonaController::class, 'index']);

});
