<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum NivelEstudioEnum: int implements HasLabel
{
    case SinEstudio = 0;
    case Primario = 1;
    case Secundaria = 2;
    case Terciario = 3;
    case Universitario = 4;
    case Doctorado = 5;
    case Maestria = 6;
    public function getLabel(): ?string
    {
        return match ($this) {
            self::SinEstudio => 'Sin Estudio',
            self::Primario => 'Primaria',
            self::Secundaria => 'Secundaria',
            self::Terciario => 'Terciario',
            self::Universitario => 'Universitario',
            self::Doctorado => 'Doctorado',
            self::Maestria => 'Maestría',
        };
    }
}