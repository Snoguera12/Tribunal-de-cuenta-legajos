<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRol
{
    public function handle(Request $request, Closure $next): Response
    {
        if(!(auth()->check() && auth()->user()->isStaffRoles())){
            abort(403, 'No tienes autorización para ver este documento.');
        }

        return $next($request);
    }
}
