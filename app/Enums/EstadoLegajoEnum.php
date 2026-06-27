<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum EstadoLegajoEnum: int implements HasLabel
{
    case Baja = 0;
    case Alta = 1;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Baja => 'Baja',
            self::Alta => 'Alta',
        };
    }
}