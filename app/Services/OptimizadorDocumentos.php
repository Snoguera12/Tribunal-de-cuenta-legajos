<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;


// gzip no ahorra en pdf/jpg (ya vienen comprimidos): se recomprimen. Jpeg con GD, pdf con ghostscript.
// si no se ahorra lo suficiente se queda el original
class OptimizadorDocumentos
{

    // devuelve el contenido (optimizado o no) y datos de lo que se hizo
    public static function optimizar(string $contenido): array
    {
        $antes = strlen($contenido);
        $resultado = ['contenido' => $contenido, 'metodo' => 'original', 'antes' => $antes, 'despues' => $antes];

        if (! config('sistema.documentos.optimizar')) {
            return $resultado;
        }

        try {

            if (str_starts_with($contenido, '%PDF-')) {
                $nuevo = self::pdf($contenido);
                $metodo = 'pdf-gs';
            } else {
                $nuevo = self::jpeg($contenido);
                $metodo = 'jpeg-gd';
            }

        } catch (\Throwable $e) {

            logger()->warning('No se pudo optimizar el documento: ' . $e->getMessage());

            return $resultado;
        }

        $minimo = 1 - config('sistema.documentos.ahorro_minimo');

        if ($nuevo !== null && strlen($nuevo) < $antes * $minimo) {
            $resultado['contenido'] = $nuevo;
            $resultado['metodo'] = $metodo;
            $resultado['despues'] = strlen($nuevo);
        }

        return $resultado;
    }


    public static function jpeg(string $contenido): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $info = @getimagesizefromstring($contenido);

        // los cmyk salen con colores raros al pasar por gd
        if (! $info || ($info['channels'] ?? 3) === 4) {
            return null;
        }

        // gd necesita unos 5 bytes por pixel; si no entra en memoria se deja el original
        if ($info[0] * $info[1] > 50_000_000 || ! self::entraEnMemoria($info[0], $info[1])) {
            return null;
        }

        $imagen = @imagecreatefromstring($contenido);

        if (! $imagen) {
            return null;
        }

        $imagen = self::girarSegunExif($imagen, $contenido);

        // se achica solo si es mas grande que el maximo
        $lado = config('sistema.documentos.jpeg_lado_maximo');
        $ancho = imagesx($imagen);
        $alto = imagesy($imagen);

        if (max($ancho, $alto) > $lado) {
            $escala = $lado / max($ancho, $alto);
            $imagen = imagescale($imagen, (int) round($ancho * $escala), (int) round($alto * $escala), IMG_BICUBIC);
        }

        imageinterlace($imagen, true);

        ob_start();
        imagejpeg($imagen, null, config('sistema.documentos.jpeg_calidad'));
        $salida = ob_get_clean();

        return $salida ?: null;
    }


    private static function entraEnMemoria(int $ancho, int $alto): bool
    {
        $limite = self::bytes((string) ini_get('memory_limit'));

        if ($limite < 0) {
            return true;
        }

        return $ancho * $alto * 5 < ($limite - memory_get_usage(true)) * 0.8;
    }


    private static function bytes(string $valor): int
    {
        $valor = trim($valor);

        if ($valor === '-1') {
            return -1;
        }

        $numero = (int) $valor;

        return match (strtoupper(substr($valor, -1))) {
            'G' => $numero * 1024 ** 3,
            'M' => $numero * 1024 ** 2,
            'K' => $numero * 1024,
            default => $numero,
        };
    }


    private static function girarSegunExif($imagen, string $contenido)
    {
        if (! function_exists('exif_read_data')) {
            return $imagen;
        }

        $exif = @exif_read_data('data://image/jpeg;base64,' . base64_encode($contenido));

        $giro = match ($exif['Orientation'] ?? 1) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        return $giro ? (imagerotate($imagen, $giro, 0) ?: $imagen) : $imagen;
    }


    public static function pdf(string $contenido): ?string
    {
        if (! self::hayGhostscript()) {
            return null;
        }

        $perfil = in_array(config('sistema.documentos.pdf_perfil'), ['screen', 'ebook', 'printer'])
            ? config('sistema.documentos.pdf_perfil')
            : 'ebook';

        $entrada = tempnam(sys_get_temp_dir(), 'docin');
        $salida = tempnam(sys_get_temp_dir(), 'docout');

        try {

            file_put_contents($entrada, $contenido);

            $resultado = Process::timeout(90)->run([
                'gs', '-sDEVICE=pdfwrite', '-dCompatibilityLevel=1.5',
                '-dPDFSETTINGS=/' . $perfil,
                '-dDetectDuplicateImages=true', '-dCompressFonts=true',
                '-dNOPAUSE', '-dBATCH', '-dQUIET', '-dSAFER',
                '-sOutputFile=' . $salida, $entrada,
            ]);

            if (! $resultado->successful()) {
                return null;
            }

            $nuevo = file_get_contents($salida);

            // tiene que seguir siendo un pdf valido
            if (! $nuevo || ! str_starts_with($nuevo, '%PDF-') || ! str_contains($nuevo, '%%EOF')) {
                return null;
            }

            return $nuevo;

        } finally {

            @unlink($entrada);
            @unlink($salida);
        }
    }


    public static function hayGhostscript(): bool
    {
        static $hay = null;

        if ($hay === null) {
            try {
                $hay = Process::timeout(5)->run(['gs', '--version'])->successful();
            } catch (\Throwable $e) {
                $hay = false;
            }
        }

        return $hay;
    }
}
