<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class TokenController extends Controller
{
    // si la petición llegó hasta acá el token sigue vigente: si fue revocado o venció, Sanctum ya respondió 401
    public function estado(Request $request): JsonResponse
    {
        $credencial = $request->user()->currentAccessToken();

        return response()->json([
            'valido' => true,
            'vence' => $credencial->expires_at?->toIso8601String(),
        ]);
    }
}