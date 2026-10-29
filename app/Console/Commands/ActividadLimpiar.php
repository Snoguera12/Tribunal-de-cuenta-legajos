<?php

namespace App\Console\Commands;

use App\Models\ActividadReciente;
use Illuminate\Console\Command;

// los "ultimos agentes" viejos no hacen falta: se borran los de mas de N dias
class ActividadLimpiar extends Command
{
    protected $signature = 'actividad:limpiar {--dias=90 : se borra lo mas viejo que esto}';

    protected $description = 'Borra los registros viejos de "ultimos agentes y usuarios" del escritorio';


    public function handle(): int
    {
        $dias = max(1, (int) $this->option('dias'));

        $borrados = ActividadReciente::where('updated_at', '<', now()->subDays($dias))->delete();

        $this->info("Se borraron {$borrados} registro(s) de más de {$dias} días.");

        return self::SUCCESS;
    }
}
