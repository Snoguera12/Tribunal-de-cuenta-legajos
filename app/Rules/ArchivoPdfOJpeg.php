<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

// mejor seguridad para archivos
// revisa que el pdf o jpg sea real, no solo por el nombre
// tambien busca codigo raro escondido adentro (como scripts)
class ArchivoPdfOJpeg implements ValidationRule
{
    // tamaño minimo que puede tener el archivo
    private const TAMANIO_MINIMO = 16;

    // cuanto se tolera de basura pegada al final
    private const SOBRANTE_MAXIMO_TOLERADO = 512;

    // palabras de codigo que no deberian estar en un pdf o jpg
    private const PATRONES_PELIGROSOS = [
        '<?php', '<script', 'javascript:',
        'base64_decode(', 'shell_exec(', 'passthru(', 'proc_open(',
        '#!/bin/', '#!/usr/bin/env',
    ];

    // firmas de otros archivos que no deberian estar escondidas en la imagen
    private const FIRMAS_EXTRANIAS_EN_IMAGEN = [
        "PK\x03\x04", // ZIP / DOCX / JAR / APK...
        "PK\x05\x06", // fin de directorio central ZIP
        "\x7FELF",    // ejecutable Linux
        "\xCA\xFE\xBA\xBE", // ejecutable Java (class) / Mach-O universal
        "MZ\x90\x00", // cabecera real de un ejecutable Windows (PE), no solo "MZ" suelto
    ];

    // cosas de pdf que pueden ejecutar algo solas (peligroso)
    private const PALABRAS_CLAVE_PDF_PELIGROSAS = [
        '/JavaScript', '/JS', '/OpenAction', '/Launch', '/EmbeddedFile',
        '/AA', '/RichMedia', '/SubmitForm', '/ImportData', '/GoToR', '/GoToE',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!($value instanceof UploadedFile)) {
            $fail('El archivo no es válido.');
            return;
        }

        $rutaReal = $value->getRealPath();
        if ($rutaReal === false || !is_readable($rutaReal)) {
            $fail('No se pudo leer el archivo subido.');
            return;
        }

        $tamanio = filesize($rutaReal);
        if ($tamanio === false || $tamanio < self::TAMANIO_MINIMO) {
            $fail('El archivo está vacío o dañado.');
            return;
        }

        // leemos todo el archivo (es chico, no pasa nada)
        $contenido = file_get_contents($rutaReal);
        if ($contenido === false) {
            $fail('No se pudo leer el contenido del archivo.');
            return;
        }

        $esPdf = str_starts_with($contenido, '%PDF-');
        $esJpeg = strlen($contenido) >= 3
            && ord($contenido[0]) === 0xFF
            && ord($contenido[1]) === 0xD8
            && ord($contenido[2]) === 0xFF;

        if (!$esPdf && !$esJpeg) {
            $fail('El archivo debe ser un PDF o una imagen JPEG real. Se detectó otro tipo de contenido, aunque el nombre del archivo diga lo contrario.');
            return;
        }

        // buscamos codigo raro adentro del archivo
        $contenidoMinuscula = strtolower($contenido);
        foreach (self::PATRONES_PELIGROSOS as $patron) {
            if (str_contains($contenidoMinuscula, strtolower($patron))) {
                $fail('El archivo contiene contenido no permitido y fue rechazado por seguridad.');
                return;
            }
        }

        if ($esJpeg) {
            $this->validarJpeg($contenido, $fail);
            return;
        }

        $this->validarPdf($contenido, $fail);
    }

    private function validarJpeg(string $contenido, Closure $fail): void
    {
        // buscamos firmas de otros formatos escondidas
        foreach (self::FIRMAS_EXTRANIAS_EN_IMAGEN as $firma) {
            if (str_contains($contenido, $firma)) {
                $fail('La imagen contiene datos incrustados no permitidos y fue rechazada por seguridad.');
                return;
            }
        }

        // el jpg tiene que tener un final real
        $posicionFin = strrpos($contenido, "\xFF\xD9");
        if ($posicionFin === false) {
            $fail('La imagen JPEG está incompleta o dañada.');
            return;
        }

        // no puede sobrar mucha basura despues del final de la imagen
        $sobrante = strlen($contenido) - ($posicionFin + 2);
        if ($sobrante > self::SOBRANTE_MAXIMO_TOLERADO) {
            $fail('La imagen contiene datos adicionales sospechosos después del final del archivo y fue rechazada por seguridad.');
        }
    }

    private function validarPdf(string $contenido, Closure $fail): void
    {
        foreach (self::PALABRAS_CLAVE_PDF_PELIGROSAS as $palabra) {
            if (str_contains($contenido, $palabra)) {
                $fail('El PDF contiene contenido activo (JavaScript, acciones automáticas o archivos embebidos) no permitido y fue rechazado por seguridad.');
                return;
            }
        }

        // un pdf real termina con %%EOF
        $posicionFin = strrpos($contenido, '%%EOF');
        if ($posicionFin === false) {
            $fail('El PDF no tiene una estructura válida y fue rechazado.');
            return;
        }

        $sobrante = strlen($contenido) - ($posicionFin + strlen('%%EOF'));
        if ($sobrante > self::SOBRANTE_MAXIMO_TOLERADO) {
            $fail('El PDF contiene datos adicionales sospechosos después de su cierre y fue rechazado por seguridad.');
        }
    }
}
