<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Valida que el archivo subido sea REALMENTE un PDF o una imagen JPEG,
 * leyendo la firma binaria de sus primeros bytes en vez de confiar en:
 *  - el nombre o la extensión del archivo (los define quien lo sube),
 *  - el Content-Type que manda el navegador (también lo controla el cliente),
 *  - únicamente la detección de libmagic/finfo del sistema operativo, que
 *    puede variar de una máquina a otra y en algunos casos no distinguir
 *    bien un .docx (que también es un archivo ZIP) de otros formatos.
 *
 * Un PDF real siempre empieza con los bytes "%PDF-".
 * Un JPEG real siempre empieza con los bytes FF D8 FF.
 * Ningún cambio de nombre de archivo puede falsificar esto sin corromper
 * el propio archivo.
 */
class ArchivoPdfOJpeg implements ValidationRule
{
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

        $handle = fopen($rutaReal, 'rb');
        $cabecera = $handle ? fread($handle, 8) : '';
        if ($handle) {
            fclose($handle);
        }

        $esPdf = str_starts_with($cabecera, '%PDF-');
        $esJpeg = strlen($cabecera) >= 3
            && ord($cabecera[0]) === 0xFF
            && ord($cabecera[1]) === 0xD8
            && ord($cabecera[2]) === 0xFF;

        if (!$esPdf && !$esJpeg) {
            $fail('El archivo debe ser un PDF o una imagen JPEG real. Se detectó otro tipo de contenido, aunque el nombre del archivo diga lo contrario.');
            return;
        }

        // Segunda verificación cruzada: el MIME que reporta el sistema
        // (finfo) también tiene que ser coherente con lo que ya confirmamos
        // leyendo los bytes a mano.
        $mime = $value->getMimeType();
        if (!in_array($mime, ['application/pdf', 'image/jpeg'], true)) {
            $fail('El tipo de archivo detectado (' . $mime . ') no está permitido. Solo se aceptan PDF y JPEG.');
        }
    }
}
