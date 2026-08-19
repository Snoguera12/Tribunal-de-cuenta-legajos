<?php

use App\Http\Controllers\DocumentoDownloadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('legajos');
});

// ruta para descargar documentos, pide login y adentro se chequea el permiso puntual
Route::get('/documentos/{documento}/descargar', DocumentoDownloadController::class)
    ->middleware(['auth'])
    ->name('documentos.descargar');
