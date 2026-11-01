<?php

namespace App\Services;

use App\Models\Token;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Envía un token recién generado a un destino aprobado (ver config/buzones.php).
 * La transferencia va por HTTPS en producción.
 */
class BuzonLicencias
{
    /**
     * Destinos disponibles para la pantalla: solo los que tienen URL configurada.
     *
     * @return array<string, string> clave => nombre
     */
    public static function destinos(): array
    {
        return collect(config('buzones'))
            ->filter(fn (array $d) => filled($d['url']))
            ->map(fn (array $d) => $d['nombre'])
            ->all();
    }

    /**
     * @return array{ok: bool, mensaje: string}
     */
    public static function enviar(string $destino, Token $token, string $textoToken, ?CarbonInterface $venceEn): array
    {
        $config = config("buzones.{$destino}");

        if (! $config || blank($config['url'])) {
            return ['ok' => false, 'mensaje' => 'El destino no está aprobado o no tiene URL configurada.'];
        }

        if (! str_starts_with($config['url'], 'https://') && app()->environment('production')) {
            return ['ok' => false, 'mensaje' => 'El buzón de destino tiene que usar HTTPS en producción.'];
        }

        $cabeceras = ['Accept' => 'application/json'];
        if (filled($config['clave'])) {
            $cabeceras['X-Buzon-Clave'] = $config['clave'];
        }

        try {
            $respuesta = Http::withHeaders($cabeceras)
                ->timeout(10)
                ->post(rtrim($config['url'], '/') . '/api/buzon/tokens', [
                    'nombre' => $token->nombre,
                    'token' => $textoToken,
                    'vence_en' => $venceEn?->toIso8601String(),
                ]);
        } catch (ConnectionException) {
            return ['ok' => false, 'mensaje' => "No se pudo conectar con {$config['nombre']}."];
        }

        if ($respuesta->successful()) {
            $envios = $token->enviados ?? [];
            $envios[$destino] = now()->toDateTimeString();
            $token->update(['enviados' => $envios]);

            return ['ok' => true, 'mensaje' => "Token enviado a {$config['nombre']}."];
        }

        return ['ok' => false, 'mensaje' => "{$config['nombre']} respondió con error {$respuesta->status()}."];
    }
}