<?php

use App\Http\Controllers\Api\AreaController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\LegajoController;
use App\Http\Controllers\Api\PersonaController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    //Route::get('/areas', [AreaController::class, 'index']);
    //Route::get('/areas/id/{id}', [AreaController::class, 'show_id']);

    //Route::get('/categorias', [CategoriaController::class, 'index']);
    //Route::get('/categorias/id/{id}', [CategoriaController::class, 'show_id']);

    //Route::get('/legajos', [LegajoController::class, 'index']);
    //Route::get('/legajos/id/{id}', [LegajoController::class, 'show_id']);
    //Route::get('/legajos/num_legajo/{num_legajo}', [LegajoController::class, 'show_num_legajo']);
    Route::get('/legajos/por-dni/{dni}', [LegajoController::class, 'show_dni']);

    Route::get('/personas', [PersonaController::class, 'index']);
    //Route::get('/personas/id/{persona:id}', [PersonaController::class, 'show_id']);
    //Route::get('/personas/dni/{persona:dni}', [PersonaController::class, 'show_dni']);

});
