<?php

namespace App\Services;

use App\Models\Auditoria;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;


// documento: se reoptimiza, gzip y aes-256-gcm con la APP_KEY. Formato: "TCS2" + iv(12) + tag(16) + datos
// el nombre sale del contenido: el mismo archivo subido dos veces ocupa lugar una vez
class ArchivoSeguro
{
    private const CIFRADO = 'aes-256-gcm';
    private const IV = 12;
    private const TAG = 16;
    private const MARCA = 'TCS2';


    public static function guardar(TemporaryUploadedFile $file, string $directorio = 'documentos'): ?string
    {
        if (! $file->exists()) {
            return null;
        }

        $original = file_get_contents($file->getRealPath());

        if ($original === false) {
            return null;
        }

        $optimizado = OptimizadorDocumentos::optimizar($original);

        $extension = str_starts_with($optimizado['contenido'], '%PDF-') ? 'pdf' : 'jpg';
        $ruta = trim($directorio, '/') . '/' . self::nombreDesdeContenido($optimizado['contenido']) . '.' . $extension;

        $repetido = Storage::disk('local')->exists($ruta);

        if (! $repetido) {
            Storage::disk('local')->put($ruta, self::encriptar($optimizado['contenido']));
        }

        $file->delete();

        Auditoria::registrar(
            evento: 'archivo_subido',
            descripcion: 'Subió un documento (' . round($optimizado['antes'] / 1024) . ' KB -> ' . round($optimizado['despues'] / 1024) . ' KB)',
            extra: [
                'ruta' => $ruta,
                'nombre_original' => $file->getClientOriginalName(),
                'bytes_original' => $optimizado['antes'],
                'bytes_guardado' => $optimizado['despues'],
                'ahorro' => $optimizado['antes'] > 0 ? round(100 - $optimizado['despues'] * 100 / $optimizado['antes']) . '%' : '0%',
                'metodo' => $optimizado['metodo'],
                'sha256_original' => hash('sha256', $original),
                'ya_existia' => $repetido ? 'si' : 'no',
            ],
        );

        return $ruta;
    }


    // hmac con la clave de la app para que no se pueda adivinar el nombre
    public static function nombreDesdeContenido(string $contenido): string
    {
        return substr(hash_hmac('sha256', $contenido, self::clave()), 0, 48);
    }


    public static function encriptar(string $contenido): string
    {
        $iv = random_bytes(self::IV);
        $tag = '';

        $cifrado = openssl_encrypt(gzencode($contenido, 9), self::CIFRADO, self::clave(), OPENSSL_RAW_DATA, $iv, $tag);

        return self::MARCA . $iv . $tag . $cifrado;
    }


    // null si no esta cifrado con esta clave
    public static function desencriptar(string $crudo): ?string
    {
        // formato nuevo con marca, si no tiene se prueba el viejo
        if (str_starts_with($crudo, self::MARCA)) {
            $crudo = substr($crudo, strlen(self::MARCA));
        }

        if (strlen($crudo) < self::IV + self::TAG) {
            return null;
        }

        $iv = substr($crudo, 0, self::IV);
        $tag = substr($crudo, self::IV, self::TAG);
        $cifrado = substr($crudo, self::IV + self::TAG);

        $comprimido = @openssl_decrypt($cifrado, self::CIFRADO, self::clave(), OPENSSL_RAW_DATA, $iv, $tag);

        if ($comprimido === false) {
            return null;
        }

        $contenido = @gzdecode($comprimido);

        return $contenido === false ? null : $contenido;
    }


    public static function esPdfOJpeg(string $contenido): bool
    {
        return str_starts_with($contenido, '%PDF-')
            || (strlen($contenido) >= 3 && ord($contenido[0]) === 0xFF && ord($contenido[1]) === 0xD8 && ord($contenido[2]) === 0xFF);
    }


    // documento original; si es uno viejo sin cifrar lo devuelve igual (si es pdf o jpg de verdad)
    public static function leer(string $ruta): ?string
    {
        if (! Storage::disk('local')->exists($ruta)) {
            return null;
        }

        $crudo = Storage::disk('local')->get($ruta);

        $contenido = self::desencriptar($crudo);

        if ($contenido !== null) {
            return $contenido;
        }

        return self::esPdfOJpeg($crudo) ? $crudo : null;
    }


    public static function tipoMime(string $ruta): string
    {
        return match (strtolower(pathinfo($ruta, PATHINFO_EXTENSION))) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'application/octet-stream',
        };
    }


    // aes-256 necesita 32 bytes, por eso se pasa la APP_KEY por sha256
    private static function clave(): string
    {
        $clave = config('app.key');

        if (str_starts_with($clave, 'base64:')) {
            $clave = base64_decode(substr($clave, 7));
        }

        return hash('sha256', $clave, true);
    }
}
