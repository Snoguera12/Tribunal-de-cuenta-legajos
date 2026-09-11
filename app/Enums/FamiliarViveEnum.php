<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum FamiliarViveEnum: int implements HasLabel
{
    case Vivo = 0;
    case Fallecido = 1;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Vivo => 'Vivo',
            self::Fallecido => 'Fallecido',
        };
    }
}