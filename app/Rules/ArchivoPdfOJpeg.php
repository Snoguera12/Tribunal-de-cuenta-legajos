<?php

namespace App\Rules;

use App\Models\Auditoria;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;


// revisa el contenido real del archivo, no solo el nombre
class ArchivoPdfOJpeg implements ValidationRule
{
    private const TAMANIO_MINIMO = 16;
    private const SOBRANTE_MAXIMO = 512;
    private const PIXELES_MAXIMOS = 100_000_000;
    private const LADO_MAXIMO = 20000;

    private const PATRONES_PELIGROSOS = [
        '<?php', '<script', 'javascript:',
        'base64_decode(', 'shell_exec(', 'passthru(', 'proc_open(',
        '#!/bin/', '#!/usr/bin/env',
    ];

    // firmas de otros archivos escondidas en una imagen
    private const FIRMAS_EXTRANIAS = [
        "PK\x03\x04", "PK\x05\x06", "\x7FELF", "\xCA\xFE\xBA\xBE", "MZ\x90\x00",
    ];

    // cosas de pdf que se ejecutan solas
    private const PDF_PELIGROSO = [
        '/JavaScript', '/JS', '/OpenAction', '/Launch', '/EmbeddedFile',
        '/AA', '/RichMedia', '/SubmitForm', '/ImportData', '/GoToR', '/GoToE',
    ];


    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! ($value instanceof UploadedFile)) {
            $fail('El archivo no es válido.');
            return;
        }

        $ruta = $value->getRealPath();

        if ($ruta === false || ! is_readable($ruta)) {
            $fail('No se pudo leer el archivo subido.');
            return;
        }

        $tamanio = filesize($ruta);

        if ($tamanio === false || $tamanio < self::TAMANIO_MINIMO) {
            $fail('El archivo está vacío o dañado.');
            return;
        }

        $contenido = file_get_contents($ruta);

        if ($contenido === false) {
            $fail('No se pudo leer el contenido del archivo.');
            return;
        }

        $esPdf = str_starts_with($contenido, '%PDF-');
        $esJpeg = ord($contenido[0]) === 0xFF && ord($contenido[1]) === 0xD8 && ord($contenido[2]) === 0xFF;

        if (! $esPdf && ! $esJpeg) {
            $this->rechazar($fail, $value, 'no es pdf ni jpeg real',
                'El archivo debe ser un PDF o una imagen JPEG real. Se detectó otro tipo de contenido, aunque el nombre diga lo contrario.');
            return;
        }

        $minuscula = strtolower($contenido);

        foreach (self::PATRONES_PELIGROSOS as $patron) {
            if (str_contains($minuscula, strtolower($patron))) {
                $this->rechazar($fail, $value, "contiene {$patron}",
                    'El archivo contiene contenido no permitido y fue rechazado por seguridad.');
                return;
            }
        }

        $esJpeg ? $this->validarJpeg($contenido, $fail, $value) : $this->validarPdf($contenido, $fail, $value);
    }


    private function validarJpeg(string $contenido, Closure $fail, UploadedFile $archivo): void
    {
        // un jpeg chico puede declarar dimensiones enormes y agotar la memoria al abrirlo
        $info = @getimagesizefromstring($contenido);

        if (! $info || $info[0] * $info[1] > self::PIXELES_MAXIMOS || max($info[0], $info[1]) > self::LADO_MAXIMO) {
            $this->rechazar($fail, $archivo, 'dimensiones de imagen invalidas o excesivas',
                'La imagen tiene dimensiones no válidas o demasiado grandes y fue rechazada.');
            return;
        }

        foreach (self::FIRMAS_EXTRANIAS as $firma) {
            if (str_contains($contenido, $firma)) {
                $this->rechazar($fail, $archivo, 'datos incrustados en la imagen',
                    'La imagen contiene datos incrustados no permitidos y fue rechazada por seguridad.');
                return;
            }
        }

        $fin = strrpos($contenido, "\xFF\xD9");

        if ($fin === false) {
            $this->rechazar($fail, $archivo, 'jpeg incompleto', 'La imagen JPEG está incompleta o dañada.');
            return;
        }

        if (strlen($contenido) - ($fin + 2) > self::SOBRANTE_MAXIMO) {
            $this->rechazar($fail, $archivo, 'datos despues del final del jpeg',
                'La imagen contiene datos adicionales sospechosos después del final del archivo y fue rechazada por seguridad.');
        }
    }


    private function validarPdf(string $contenido, Closure $fail, UploadedFile $archivo): void
    {
        foreach (self::PDF_PELIGROSO as $palabra) {
            if (str_contains($contenido, $palabra)) {
                $this->rechazar($fail, $archivo, "pdf con {$palabra}",
                    'El PDF contiene contenido activo (JavaScript, acciones automáticas o archivos embebidos) no permitido y fue rechazado por seguridad.');
                return;
            }
        }

        $fin = strrpos($contenido, '%%EOF');

        if ($fin === false) {
            $this->rechazar($fail, $archivo, 'pdf sin %%EOF', 'El PDF no tiene una estructura válida y fue rechazado.');
            return;
        }

        if (strlen($contenido) - ($fin + 5) > self::SOBRANTE_MAXIMO) {
            $this->rechazar($fail, $archivo, 'datos despues del cierre del pdf',
                'El PDF contiene datos adicionales sospechosos después de su cierre y fue rechazado por seguridad.');
        }
    }


    // avisa al usuario y deja el intento en la auditoria
    private function rechazar(Closure $fail, UploadedFile $archivo, string $motivo, string $mensaje): void
    {
        Auditoria::registrar(
            evento: 'archivo_rechazado',
            descripcion: 'Se rechazó un archivo subido: ' . $motivo,
            extra: [
                'nombre' => $archivo->getClientOriginalName(),
                'bytes' => $archivo->getSize(),
                'sha256' => hash_file('sha256', $archivo->getRealPath()),
            ],
        );

        $fail($mensaje);
    }
}
