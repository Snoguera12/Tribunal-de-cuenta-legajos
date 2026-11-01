<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TokenDuracion: string implements HasLabel
{
    case QuinceMinutos = 'quince_minutos';
    case MediaHora = 'media_hora';
    case UnaHora = 'una_hora';
    case SeisHoras = 'seis_horas';
    case UnDia = 'un_dia';

    public function getLabel(): string
    {
        return match ($this) {
            self::QuinceMinutos => 'Quince Minutos',
            self::MediaHora => 'Media hora',
            self::UnaHora => 'Una hora',
            self::SeisHoras => 'Seis horas (jornada)',
            self::UnDia => 'Un día',
        };
    }

    public function expiraEn()
    {
        return match ($this) {
            self::QuinceMinutos => now()->addMinutes(15),
            self::MediaHora => now()->addMinutes(30),
            self::UnaHora => now()->addHour(),
            self::SeisHoras => now()->addHours(6),
            self::UnDia => now()->addDay(),
        };
    }
}
