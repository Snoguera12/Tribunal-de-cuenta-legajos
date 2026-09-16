<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum GeneroEnum: int implements HasLabel
{
    case Femenino = 0;
    case Masculino = 1;
    case Otro = 2;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Femenino => 'Femenino',
            self::Masculino => 'Masculino',
            self::Otro => 'Otro',
        };
    }
}