<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum IdiomaNivelEnum: int implements HasLabel
{
    case Basico = 0;
    case Intercambio = 1;
    case Avanzado = 2;
    case Nativo = 3;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Basico => 'Básico',
            self::Intercambio => 'Intercambio',
            self::Avanzado => 'Avanzado',
            self::Nativo => 'Nativo',
        };
    }
}