<?php

namespace App\Console\Commands;

use App\Services\HistoricoService;
use Illuminate\Console\Command;


class HistoricoRestaurar extends Command
{
    protected $signature = 'historico:restaurar {tabla} {id} {version : número de versión del historial} {--si : no pregunta}';

    protected $description = 'Vuelve un registro a una versión vieja guardada en el historial';


    public function handle(): int
    {
        $tabla = $this->argument('tabla');
        $version = HistoricoService::versiones($tabla, $this->argument('id'))
            ->firstWhere('hist_version', (int) $this->argument('version'));

        if (! $version) {
            $this->error('No existe esa versión.');
            return self::FAILURE;
        }

        $this->table(['campo', 'valor'], collect((array) $version)
            ->map(fn ($v, $k) => [$k, mb_strimwidth((string) $v, 0, 80, '…')])->values()->all());

        if (! $this->option('si') && ! $this->confirm('¿Restaurar este registro?')) {
            return self::SUCCESS;
        }

        $error = HistoricoService::restaurar($tabla, $this->argument('id'), (int) $this->argument('version'));

        if ($error) {
            $this->error($error);
            return self::FAILURE;
        }

        $this->info('Listo.');

        return self::SUCCESS;
    }
}
