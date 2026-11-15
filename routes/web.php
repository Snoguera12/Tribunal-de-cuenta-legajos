<?php
use App\Http\Controllers\DocumentoDownloadController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/sistema'))->where('path', '.+');

Route::get('/login', fn () => redirect()->to('/sistema/login'))->name('login');

Route::get('/documentos/{documento}', DocumentoDownloadController::class)
->middleware(['auth', 'signed'])
->name('documentos.ver');