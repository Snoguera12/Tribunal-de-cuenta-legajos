<?php

namespace App\Rules;

<<<<<<< HEAD
=======
use App\Models\Auditoria;
>>>>>>> version2-20261014_210855
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

<<<<<<< HEAD
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
=======

// revisa el contenido real del archivo, no solo el nombre
class ArchivoPdfOJpeg implements ValidationRule
{
    private const TAMANIO_MINIMO = 16;
    private const SOBRANTE_MAXIMO = 512;
    private const PIXELES_MAXIMOS = 100_000_000;
    private const LADO_MAXIMO = 20000;

>>>>>>> version2-20261014_210855
    private const PATRONES_PELIGROSOS = [
        '<?php', '<script', 'javascript:',
        'base64_decode(', 'shell_exec(', 'passthru(', 'proc_open(',
        '#!/bin/', '#!/usr/bin/env',
    ];

<<<<<<< HEAD
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
=======
    // firmas de otros archivos escondidas en una imagen
    private const FIRMAS_EXTRANIAS = [
        "PK\x03\x04", "PK\x05\x06", "\x7FELF", "\xCA\xFE\xBA\xBE", "MZ\x90\x00",
    ];

    // cosas de pdf que se ejecutan solas
    private const PDF_PELIGROSO = [
>>>>>>> version2-20261014_210855
        '/JavaScript', '/JS', '/OpenAction', '/Launch', '/EmbeddedFile',
        '/AA', '/RichMedia', '/SubmitForm', '/ImportData', '/GoToR', '/GoToE',
    ];

<<<<<<< HEAD
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!($value instanceof UploadedFile)) {
=======

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! ($value instanceof UploadedFile)) {
>>>>>>> version2-20261014_210855
            $fail('El archivo no es válido.');
            return;
        }

<<<<<<< HEAD
        $rutaReal = $value->getRealPath();
        if ($rutaReal === false || !is_readable($rutaReal)) {
=======
        $ruta = $value->getRealPath();

        if ($ruta === false || ! is_readable($ruta)) {
>>>>>>> version2-20261014_210855
            $fail('No se pudo leer el archivo subido.');
            return;
        }

<<<<<<< HEAD
        $tamanio = filesize($rutaReal);
=======
        $tamanio = filesize($ruta);

>>>>>>> version2-20261014_210855
        if ($tamanio === false || $tamanio < self::TAMANIO_MINIMO) {
            $fail('El archivo está vacío o dañado.');
            return;
        }

<<<<<<< HEAD
        // leemos todo el archivo (es chico, no pasa nada)
        $contenido = file_get_contents($rutaReal);
=======
        $contenido = file_get_contents($ruta);

>>>>>>> version2-20261014_210855
        if ($contenido === false) {
            $fail('No se pudo leer el contenido del archivo.');
            return;
        }

        $esPdf = str_starts_with($contenido, '%PDF-');
<<<<<<< HEAD
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
=======
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
>>>>>>> version2-20261014_210855
                return;
            }
        }

<<<<<<< HEAD
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
=======
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
>>>>>>> version2-20261014_210855
                return;
            }
        }

<<<<<<< HEAD
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
=======
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
>>>>>>> version2-20261014_210855
                return;
            }
        }

<<<<<<< HEAD
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
=======
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
>>>>>>> version2-20261014_210855
}
