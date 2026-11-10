<?php

namespace App\Console\Commands;

use App\Services\CopiaDeSeguridad;
use Illuminate\Console\Command;


class SistemaRestaurar extends Command
{
    protected $signature = 'sistema:restaurar {archivo} {--solo-bases} {--solo-documentos} {--si : no pregunta}';

    protected $description = 'Restaura un backup (antes hace otro de lo que hay ahora)';


    public function handle(CopiaDeSeguridad $copia): int
    {
        $this->warn('Esto reemplaza las bases y/o los documentos actuales.');

        if (! $this->option('si') && ! $this->confirm('Seguro que queres seguir?')) {
            return self::SUCCESS;
        }

        try {
            $hecho = $copia->restaurar(
                $this->argument('archivo'),
                bases: ! $this->option('solo-documentos'),
                documentos: ! $this->option('solo-bases'),
            );
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        foreach ($hecho as $linea) {
            $this->line($linea);
        }

        return self::SUCCESS;
    }
}
