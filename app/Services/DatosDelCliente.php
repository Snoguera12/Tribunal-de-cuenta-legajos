<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;


class DatosDelCliente
{

    // la mac solo se puede leer (tabla arp) si la pc esta en la misma red que el servidor
    public static function mac(?string $ip): ?string
    {
        if (! $ip || ! filter_var($ip, FILTER_VALIDATE_IP)) {
            return null;
        }

        // ip publica o local del propio servidor: no tiene mac util
        $esPrivada = ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE);
        if (! $esPrivada) {
            return null;
        }

        return Cache::remember('mac_' . $ip, 600, fn () => self::buscarMac($ip));
    }


    private static function buscarMac(string $ip): ?string
    {
        $comandos = PHP_OS_FAMILY === 'Windows'
            ? [['arp', '-a', $ip]]
            : [['ip', 'neigh', 'show', $ip], ['arp', '-n', $ip]];

        foreach ($comandos as $comando) {
            try {
                $resultado = Process::timeout(2)->run($comando);
            } catch (\Throwable $e) {
                continue;
            }

            $mac = self::sacarMac($resultado->output());

            if ($mac) {
                return $mac;
            }
        }

        return null;
    }


    // busca algo como aa:bb:cc:dd:ee:ff o aa-bb-cc-dd-ee-ff en un texto
    public static function sacarMac(string $texto): ?string
    {
        if (preg_match('/\b([0-9a-f]{2}([:-])(?:[0-9a-f]{2}\2){4}[0-9a-f]{2})\b/i', $texto, $coincidencia)) {

            $mac = strtolower(str_replace('-', ':', $coincidencia[1]));

            // 00:00:00:00:00:00 y ff:ff:... no son equipos reales
            if (in_array($mac, ['00:00:00:00:00:00', 'ff:ff:ff:ff:ff:ff'])) {
                return null;
            }

            return $mac;
        }

        return null;
    }


    // saca navegador, sistema y tipo de dispositivo del user agent
    public static function navegador(?string $ua): array
    {
        $ua = (string) $ua;

        $navegador = 'Desconocido';

        foreach ([
            'Edg' => 'Edge',
            'OPR' => 'Opera',
            'Firefox' => 'Firefox',
            'Chrome' => 'Chrome',
            'Safari' => 'Safari',
            'MSIE' => 'Internet Explorer',
            'Trident' => 'Internet Explorer',
        ] as $clave => $nombre) {

            if (stripos($ua, $clave) !== false) {
                $navegador = $nombre;

                if (preg_match('/' . ($clave === 'Safari' ? 'Version' : $clave) . '\/(\d+)/', $ua, $v)) {
                    $navegador .= ' ' . $v[1];
                }
                break;
            }
        }

        $so = 'Desconocido';

        foreach ([
            'Windows NT 10' => 'Windows 10/11',
            'Windows NT 6.3' => 'Windows 8.1',
            'Windows NT 6.1' => 'Windows 7',
            'Windows' => 'Windows',
            'Android' => 'Android',
            'iPhone' => 'iOS',
            'iPad' => 'iPadOS',
            'Mac OS X' => 'macOS',
            'Linux' => 'Linux',
        ] as $clave => $nombre) {

            if (stripos($ua, $clave) !== false) {
                $so = $nombre;
                break;
            }
        }

        $dispositivo = 'pc';

        if (stripos($ua, 'iPad') !== false || stripos($ua, 'Tablet') !== false) {
            $dispositivo = 'tablet';
        } elseif (stripos($ua, 'Mobi') !== false || stripos($ua, 'iPhone') !== false) {
            $dispositivo = 'celular';
        }

        return ['navegador' => $navegador, 'so' => $so, 'dispositivo' => $dispositivo];
    }
}
