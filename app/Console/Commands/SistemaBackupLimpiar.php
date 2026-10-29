<?php

namespace App\Console\Commands;

use App\Services\CopiaDeSeguridad;
use Illuminate\Console\Command;


class SistemaBackupLimpiar extends Command
{
    protected $signature = 'sistema:backup-limpiar';

    protected $description = 'Borra los backups viejos segun la politica de retencion';


    public function handle(CopiaDeSeguridad $copia): int
    {
        $borrados = $copia->limpiar();

        foreach ($borrados as $nombre) {
            $this->line("borrado: {$nombre}");
        }

        $this->info(count($borrados) . ' backups borrados.');

        return self::SUCCESS;
    }
}
