<?php

namespace App\Console\Commands;

use App\Services\HistoricoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;


class HistoricoSincronizar extends Command
{
    protected $signature = 'historico:sincronizar {--copiar-actual : copia tambien los datos que ya estan cargados}';

    protected $description = 'Crea o actualiza las tablas historicas (h_*) en la base aparte';


    public function handle(): int
    {
        foreach (config('sistema.tablas_historico') as $tabla) {

            if (! Schema::hasTable($tabla)) {
                $this->warn("{$tabla}: no existe en la base principal, se saltea");
                continue;
            }

            $estado = HistoricoService::sincronizarTabla($tabla);
            $this->line("{$tabla}: {$estado}");

            if ($this->option('copiar-actual')) {
                $copiados = HistoricoService::copiarActual($tabla);
                $this->line("    filas base copiadas: {$copiados}");
            }
        }

        return self::SUCCESS;
    }
}
