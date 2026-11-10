<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Throwable;

/**
 * Pasa un archivo por un antivirus de linea de comandos (ClamAV u otro) si esta activado.
 * El comando se ejecuta SIN shell (no se puede inyectar nada con el nombre del archivo).
 */
class Antivirus
{
    public const LIMPIO = 'limpio';
    public const AMENAZA = 'amenaza';
    public const ERROR = 'error';
    public const APAGADO = 'apagado';

    public static function activo(): bool
    {
        return (bool) config('sistema.antivirus.activo');
    }

    // devuelve [estado, detalle]
    public static function escanear(string $ruta): array
    {
        if (! self::activo()) {
            return [self::APAGADO, 'antivirus desactivado'];
        }

        $comando = preg_split('/\s+/', trim((string) config('sistema.antivirus.comando')), -1, PREG_SPLIT_NO_EMPTY);

        if (! $comando) {
            return [self::ERROR, 'no hay comando de antivirus configurado'];
        }

        try {
            $proceso = new Process([...$comando, $ruta]);
            $proceso->setTimeout(max(1, (int) config('sistema.antivirus.timeout', 30)));
            $proceso->run();
        } catch (Throwable $e) {
            return [self::ERROR, $e->getMessage()];
        }

        $detalle = trim(mb_substr($proceso->getOutput() . ' ' . $proceso->getErrorOutput(), 0, 300));

        return match ($proceso->getExitCode()) {
            0 => [self::LIMPIO, $detalle],
            1 => [self::AMENAZA, $detalle],
            default => [self::ERROR, 'el antivirus terminó con el código ' . $proceso->getExitCode() . ' ' . $detalle],
        };
    }
}
