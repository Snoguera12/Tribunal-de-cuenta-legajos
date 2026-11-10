<?php

namespace App\Rules;

use App\Models\Auditoria;
use App\Services\Antivirus;
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

    // cosas de pdf que se ejecutan solas. Se buscan como nombre completo (/AA, no /AAAAAA+Arial,
    // que es el nombre de fuente que usa Chrome al guardar como PDF)
    private const PDF_PELIGROSO = [
        'JavaScript', 'JS', 'OpenAction', 'Launch', 'EmbeddedFile',
        'AA', 'RichMedia', 'SubmitForm', 'ImportData', 'GoToR', 'GoToE',
    ];


    // $registrar = false sirve para comprobar un archivo sin dejar rastro en la auditoría ni llamar al antivirus
    public function __construct(private bool $registrar = true)
    {
    }


    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // solo se revisan las subidas nuevas: si el archivo se quitó del formulario, o ya estaba guardado
        // (ya se revisó cuando se subió), no hay nada que comprobar y no se debe mostrar ningún error
        if (! ($value instanceof UploadedFile)) {
            return;
        }

        $ruta = $value->getRealPath();

        if ($ruta === false || ! is_file($ruta)) {
            return;
        }

        if (! is_readable($ruta)) {
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

        // la norma de PDF permite algo de basura antes de la cabecera (los primeros 1024 bytes)
        $esPdf = str_contains(substr($contenido, 0, 1024), '%PDF-');
        $esJpeg = ord($contenido[0]) === 0xFF && ord($contenido[1]) === 0xD8 && ord($contenido[2]) === 0xFF;

        if (! $esPdf && ! $esJpeg) {
            $this->rechazar($fail, $value, 'no es pdf ni jpeg real (primeros bytes: ' . bin2hex(substr($contenido, 0, 12))
                . ', tamaño: ' . strlen($contenido) . ', nombre: ' . $value->getClientOriginalName() . ')',
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

        // se anota si alguna de las revisiones lo rechazo, para no pasarlo por el antivirus (que es mas lento)
        $rechazado = false;
        $fallar = function (string $mensaje) use ($fail, &$rechazado): void {
            $rechazado = true;
            $fail($mensaje);
        };

        $esJpeg ? $this->validarJpeg($contenido, $fallar, $value) : $this->validarPdf($contenido, $fallar, $value);

        if (! $rechazado && $this->registrar) {
            $this->pasarPorAntivirus($ruta, $fail, $value);
        }
    }


    // solo si ANTIVIRUS_ACTIVO=true: una amenaza se rechaza; si el programa falla, segun ANTIVIRUS_SI_FALLA
    private function pasarPorAntivirus(string $ruta, Closure $fail, UploadedFile $archivo): void
    {
        [$estado, $detalle] = Antivirus::escanear($ruta);

        if ($estado === Antivirus::AMENAZA) {
            $this->rechazar($fail, $archivo, 'el antivirus detectó una amenaza: ' . $detalle,
                'El archivo fue rechazado: el antivirus detectó una amenaza.');

            return;
        }

        if ($estado === Antivirus::ERROR) {
            Auditoria::registrar(
                evento: 'antivirus_error',
                descripcion: 'El antivirus no pudo revisar un archivo: ' . $detalle,
                extra: ['nombre' => $archivo->getClientOriginalName()],
            );

            if (config('sistema.antivirus.si_falla') === 'rechazar') {
                $fail('No se pudo revisar el archivo con el antivirus y por eso fue rechazado. Probá de nuevo más tarde.');
            }
        }
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
            // /OpenAction seguido de [ ... ] es solo "al abrir, mostrar esta pagina" (un destino): no ejecuta nada
            // y lo escriben muchos programas. Si apunta a otro objeto (/OpenAction 5 0 R) o a un diccionario
            // puede ser una accion real, y eso se sigue rechazando.
            $permitirDestino = $palabra === 'OpenAction' ? '(?!\s*\[)' : '';

            if (preg_match('#/' . $palabra . $permitirDestino . '(?![A-Za-z0-9_+.-])#', $contenido)) {
                $this->rechazar($fail, $archivo, "pdf con /{$palabra}",
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
        if (! $this->registrar) {
            $fail($mensaje);

            return;
        }

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
