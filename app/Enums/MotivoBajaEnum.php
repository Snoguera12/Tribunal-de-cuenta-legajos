<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum MotivoBajaEnum: int implements HasLabel
{
    case Soltaro = 0;
    case Casado = 1;
    case Viudo = 2;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Soltaro => 'Soltero/a',
            self::Casado => 'Casado/a',
            self::Viudo => 'Viudo/a',
        };
    }
}