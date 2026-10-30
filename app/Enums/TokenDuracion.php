<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Carbon;

enum TokenDuracion: string implements HasLabel
{
    case MediaHora = 'media_hora';
    case UnaHora = 'una_hora';
    case SeisHoras = 'seis_horas';
    case FinJornada = 'fin_jornada';
    case UnDia = 'un_dia';

    public function getLabel(): string
    {
        return match ($this) {
            self::MediaHora => 'Media hora',
            self::UnaHora => 'Una hora',
            self::SeisHoras => 'Seis horas (jornada)',
            self::FinJornada => 'Hasta el fin de la jornada laboral',
            self::UnDia => 'Un día',
        };
    }

    public function expiraEn(): Carbon
    {
        return match ($this) {
            self::MediaHora => now()->addMinutes(30),
            self::UnaHora => now()->addHour(),
            self::SeisHoras => now()->addHours(6),
            self::FinJornada => self::finDeJornada(),
            self::UnDia => now()->addDay(),
        };
    }

    // Hora de fin de jornada configurable en .env (FIN_JORNADA=18:00).
    // Si ya pasó la hora hoy, vence mañana a esa hora.
    private static function finDeJornada(): Carbon
    {
        [$hora, $minutos] = array_map('intval', explode(':', config('sistema.fin_jornada', '18:00')));

        $fin = now()->setTime($hora, $minutos, 0);

        return now()->greaterThanOrEqualTo($fin) ? $fin->addDay() : $fin;
    }
}
