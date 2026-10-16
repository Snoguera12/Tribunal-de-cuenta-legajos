<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;


enum ParentescoEnum: int implements HasLabel
{
    case Conyuge = 0;
    case Padres = 1;
    case Hijos = 2;
    case Sobrinos = 3;
    case Suegros = 4;
    public function getLabel(): ?string
    {
        return match ($this) {
            self::Conyuge => 'Cónyuge',
            self::Padres => 'Padres',
            self::Hijos => 'Hijos',
            self::Sobrinos => 'Sobrinos',
            self::Suegros => 'Suegros',
        };
    }
}