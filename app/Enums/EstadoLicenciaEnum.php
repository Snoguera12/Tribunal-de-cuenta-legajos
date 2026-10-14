<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum EstadoLicenciaEnum: string implements HasColor, HasIcon, HasLabel
{
    case Pendiente = 'pendiente';
    case Validada = 'validada';
    case Rechazada = 'rechazada';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Validada => 'Validada',
            self::Rechazada => 'Rechazada',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pendiente => 'warning',
            self::Validada => 'success',
            self::Rechazada => 'danger',
        };
    }

    public function getIcon(): string|BackedEnum|null
    {
        return match ($this) {
            self::Pendiente => Heroicon::Clock,
            self::Validada => Heroicon::CheckCircle,
            self::Rechazada => Heroicon::XCircle,
        };
    }
}
