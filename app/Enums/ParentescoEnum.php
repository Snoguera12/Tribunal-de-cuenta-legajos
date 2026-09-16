<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;


enum ParentescoEnum: int implements HasLabel
{
    /*case Abuelo = 0;
    case Conyuge = 1;
    case Cuñado = 2;
    Case Hermano = 3;
    case Hijo = 4;
    case Nieto = 5;
    case Sobrino = 6;
    case Tio = 7;
    case Suegro = 8;
    case Yerno_Nuera = 9;
    public function getLabel(): ?string
    {
        return match ($this) {
            self::Abuelo => 'Abuelo/a',
            self::Conyuge => 'Cónyuge',
            self::Cuñado => 'Cuñado/a',
            self::Hermano => 'Hijo/a',
            self::Hijo => 'Hijo/a',
            self::Nieto => 'Nieto/a',
            self::Sobrino => 'Sobrino/a',
            self::Tio => 'Tío/a',
            self::Suegro => 'Suegro/a',
            self::Yerno_Nuera => 'Yerno/Nuera',
        };
    }*/
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