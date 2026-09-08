<?php
use App\Models\Legajo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

Route::get('/', function () {
    return redirect('legajos');
});

Route::get('/login', function () {
    return redirect()->to('/legajos/login');
})->name('login');

// Dirección de documentos
Route::get('/{path}', function (Request $request, string $path) {
    if (!auth()->check()) {
        abort(401); // Usuario no autorizado.
    }
    
    if (!$request->hasValidSignature()) {
        abort(403, 'El enlace sea ha expirado.');
    }

    $legajoId = $request->route('legajo_id') ?? $request->query('legajo_id');
    $legajo = Legajo::select('id', 'persona_id')->find($legajoId);
    $usuario = auth()->user();
    if (!$usuario->isStaffRoles()){
        // Si NO es staff, obligatoriamente ambos deben tener una persona asignada (no ser null) 
        // Y además, esa persona_id debe coincidir exactamente.
        $tieneMismaPersona = 
        !is_null($usuario->persona_id) 
        && !is_null($legajo->persona_id) 
        && $usuario->persona_id === $legajo->persona_id;
        
        if (!$tieneMismaPersona) {
            abort(403, 'No tienes autorización para ver este documento de esta persona.');
        }
    }

    if (!Storage::disk('local')->exists($path)) {
        abort(404);
    }

    // Obtiene la ruta física real del archivo privado de forma segura
    $realPath = Storage::disk('local')->path($path);

    // Retorna el archivo directamente (el navegador intentará renderizarlo si es PDF/imagen)
    return Response::file($realPath);
})
->where('path', '.*',)
->name('documentos.ver');
