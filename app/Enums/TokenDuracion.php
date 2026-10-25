<?php
namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Carbon;

enum TokenDuracion: string implements HasLabel
{
    case UnMinuto = 'Un_minuto';
    case MediaHora = 'media_hora';
    case UnaHora = 'una_hora';
    case TresHoras = 'tres_horas';
    case FinJornada = 'fin_jornada';

    public function getLabel(): string
    {
        return match ($this) {
            self::UnMinuto => 'Un minuto',
            self::MediaHora => 'Media hora',
            self::UnaHora => 'Una hora',
            self::TresHoras => 'Tres horas',
            self::FinJornada => 'Hasta el fin de la jornada laboral',
        };
    }

    public function expiraEn(): Carbon
    {
        return match ($this) {
            self::UnMinuto => now()->addMinutes(1),
            self::MediaHora => now()->addMinutes(30),
            self::UnaHora => now()->addHour(),
            self::TresHoras => now()->addHours(3),
            self::FinJornada => now()->setTime(18, 0) // ← ajustar
                ->when(now()->gte(now()->setTime(18, 0)), fn ($d) => $d->addDay()),
        };
    }
}