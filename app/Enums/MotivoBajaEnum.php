<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum MotivoBajaEnum: int implements HasLabel
{
    case Renuncia = 0;
    case Despido = 1;
    case VencimientoContrato = 2;
    case Jubilacion = 3;
    case Fallecimiento = 4;
    case Incapacidad = 5;
    case Traslado = 6;
    
    public function getLabel(): ?string
    {
        return match ($this) {
            self::Renuncia => 'Renuncia',
            self::Despido => 'Despido',
            self::VencimientoContrato => 'Vencimiento de Contrato',
            self::Jubilacion => 'Jubilación',
            self::Fallecimiento => 'Fallecimiento',
            self::Incapacidad => 'Incapacidad',
            self::Traslado => 'Traslado',
        };
    }
}