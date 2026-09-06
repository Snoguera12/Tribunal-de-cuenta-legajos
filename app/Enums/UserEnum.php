<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum UserEnum: int implements HasLabel
{
    case Empleado = 1;
    case Funcionario = 2;
    case RRHH = 3;
    case Administrador = 4;
    public function getLabel(): ?string
    {
        return match ($this) {
            self::Empleado => 'Empleado',
            self::Funcionario => 'Funcionario',
            self::RRHH => 'RRHH',
            self::Administrador => 'Administrador',
        };
    }
}