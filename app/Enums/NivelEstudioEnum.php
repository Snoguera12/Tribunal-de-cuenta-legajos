<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum NivelEstudioEnum: int implements HasLabel
{
    case SinEstudio = 0;
    case Primario_No = 1;
    case Primario = 2;
    case Secundario_No = 3;
    case Secundario = 4;
    case Terciario_No = 5;
    case Terciario = 6;
    case Universitario = 7;
    case Doctorado = 8;
    case Maestria = 9;
    public function getLabel(): ?string
    {
        return match ($this) {
            self::SinEstudio => 'Sin Estudio',
            self::Primario_No => 'Primaria No Terminado',
            self::Primario => 'Primaria',
            self::Secundario_No => 'Secundaria No Terminado',
            self::Secundario => 'Secundaria',
            self::Terciario_No => 'Terciaria No Terminado',
            self::Terciario => 'Terciaria',
            self::Universitario => 'Universitario',
            self::Doctorado => 'Doctorado',
            self::Maestria => 'Maestría',
        };
    }
}