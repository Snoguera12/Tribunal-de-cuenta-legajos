<?php

namespace App\Services;

use App\Models\Token;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Envía un token recién generado al buzón del sistema de licencias.
 * La transferencia va por HTTPS (la URL tiene que empezar con https://).
 */
class BuzonLicencias
{
    /**
     * @return array{ok: bool, mensaje: string}
     */
    public static function enviar(Token $token, string $textoToken, ?CarbonInterface $venceEn): array
    {
        $url = config('services.licencias.buzon_url');
        if (blank($url)) {
            return ['ok' => false, 'mensaje' => 'Falta configurar LICENCIAS_BUZON_URL en el .env.'];
        }

        if (! str_starts_with($url, 'https://') && app()->environment('production')) {
            return ['ok' => false, 'mensaje' => 'El buzón de licencias tiene que usar HTTPS en producción.'];
        }

        $cabeceras = ['Accept' => 'application/json'];
        if ($clave = config('services.licencias.buzon_clave')) {
            $cabeceras['X-Buzon-Clave'] = $clave;
        }

        try {
            $respuesta = Http::withHeaders($cabeceras)
                ->timeout(10)
                ->post(rtrim($url, '/') . '/api/buzon/tokens', [
                    'nombre' => $token->nombre,
                    'token' => $textoToken,
                    'vence_en' => $venceEn?->toIso8601String(),
                ]);
        } catch (ConnectionException) {
            return ['ok' => false, 'mensaje' => 'No se pudo conectar con el buzón de licencias.'];
        }

        if ($respuesta->successful()) {
            $token->update(['enviado_at' => now()]);

            return ['ok' => true, 'mensaje' => 'Token enviado al buzón de licencias.'];
        }

        return ['ok' => false, 'mensaje' => "El buzón de licencias respondió con error {$respuesta->status()}."];
    }
}
