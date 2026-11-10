<?php

namespace App\Services;


// Excel y LibreOffice ejecutan como fórmula una celda que empieza con = + - @ (inyección de fórmulas en un CSV/XLSX).
// En los datos exportados se le antepone un apóstrofo para que se vea como texto.
class ProtegeFormulas
{
    public static function texto(mixed $valor): mixed
    {
        if (! is_string($valor) || $valor === '') {
            return $valor;
        }

        // un teléfono tipo "+54 381 555-1234" es legítimo y no es una fórmula
        if (preg_match('/^\+[\d\s\-().]+$/', $valor)) {
            return $valor;
        }

        return in_array($valor[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'" . $valor : $valor;
    }
}
