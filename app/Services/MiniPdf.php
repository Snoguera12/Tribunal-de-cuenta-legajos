<?php

namespace App\Services;


// arma un pdf simple de una hoja (texto con acentos) sin librerias externas
class MiniPdf
{

    // $lineas: cada una es [texto, tamaño, negrita]
    public static function hoja(string $titulo, array $lineas, string $pie = ''): string
    {
        $contenido = "0.20 0.30 0.45 rg 40 780 515 36 re f\n";
        $contenido .= "BT /F2 16 Tf 1 1 1 rg 52 792 Td " . self::texto($titulo) . " Tj ET\n";
        $contenido .= "0 0 0 rg 0.6 G 40 40 515 730 re S\n";

        $y = 745;

        foreach ($lineas as $linea) {

            [$texto, $tamanio, $negrita] = $linea + ['', 10, false];

            foreach (self::cortar($texto, (int) (95 * 10 / max($tamanio, 8))) as $parte) {
                $contenido .= 'BT /' . ($negrita ? 'F2' : 'F1') . " {$tamanio} Tf 0 0 0 rg 52 {$y} Td " . self::texto($parte) . " Tj ET\n";
                $y -= $tamanio + 5;
            }

            if ($y < 70) {
                break;
            }
        }

        $contenido .= 'BT /F1 8 Tf 0.4 0.4 0.4 rg 52 50 Td ' . self::texto($pie ?: 'DOCUMENTO DE PRUEBA - DATOS FICTICIOS') . " Tj ET\n";

        $objetos = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> >>',
            4 => '<< /Length ' . strlen($contenido) . " >>\nstream\n{$contenido}endstream",
            5 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            6 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
        ];

        $pdf = "%PDF-1.4\n";
        $posiciones = [];

        foreach ($objetos as $numero => $cuerpo) {
            $posiciones[$numero] = strlen($pdf);
            $pdf .= "{$numero} 0 obj\n{$cuerpo}\nendobj\n";
        }

        $inicioXref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objetos) + 1) . "\n0000000000 65535 f \n";

        foreach ($posiciones as $posicion) {
            $pdf .= sprintf("%010d 00000 n \n", $posicion);
        }

        return $pdf . "trailer\n<< /Size " . (count($objetos) + 1) . " /Root 1 0 R >>\nstartxref\n{$inicioXref}\n%%EOF\n";
    }


    // texto en windows-1252 entre parentesis (asi se ven los acentos)
    private static function texto(string $texto): string
    {
        $texto = mb_convert_encoding($texto, 'Windows-1252', 'UTF-8');

        return '(' . str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $texto) . ')';
    }


    private static function cortar(string $texto, int $ancho): array
    {
        return $texto === '' ? [''] : explode("\n", wordwrap($texto, $ancho, "\n", true));
    }
}
