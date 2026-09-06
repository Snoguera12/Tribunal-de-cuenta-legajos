<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum FamiliarViveEnum: int implements HasLabel
{
    case Fallecido = 0;
    case Vivo = 1;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Fallecido => 'Fallecido',
            self::Vivo => 'Vivo',
        };
    }
}