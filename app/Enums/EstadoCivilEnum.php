<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum EstadoCivilEnum: int implements HasLabel
{
    case Soltero = 0;
    case Casado = 1;
    case Divorciado = 2;
    case Viudo = 3;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Soltero => 'Soltero/a',
            self::Casado => 'Casado/a',
            self::Divorciado => 'Divorciado/a',
            self::Viudo => 'Viudo/a',
        };
    }
}