<?php
use App\Http\Controllers\DocumentoDownloadController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/legajos'))->where('path', '.+');

Route::get('/login', fn () => redirect()->to('/legajos/login'))->name('login');

Route::get('/documentos/{documento}', DocumentoDownloadController::class)
->middleware(['auth', 'signed'])
->name('documentos.ver');