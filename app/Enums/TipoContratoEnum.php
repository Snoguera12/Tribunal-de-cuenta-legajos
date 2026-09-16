<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TipoContratoEnum: int implements HasLabel
{
    case Funcionario = 0;
    case Locacion = 1;
    case Permanente = 2;
    public function getLabel(): ?string
    {
        return match ($this) {
            self::Funcionario => 'Funcionario',
            self::Locacion => 'Locación',
            self::Permanente => 'Permanente',
        };
    }
}