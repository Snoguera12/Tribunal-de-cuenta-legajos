<?php

namespace App\Console\Commands;

use App\Services\HistoricoService;
use Illuminate\Console\Command;


class HistoricoSincronizar extends Command
{
    protected $signature = 'historico:sincronizar {--copiar-actual : copia también los datos que ya están cargados (versión base)}';

    protected $description = 'Crea o actualiza las tablas h_* del historial de datos';


    public function handle(): int
    {
        foreach (array_keys(HistoricoService::modelos()) as $tabla) {

            $estado = HistoricoService::sincronizarTabla($tabla);
            $linea = HistoricoService::nombre($tabla) . ': ' . $estado;

            if ($this->option('copiar-actual')) {
                $linea .= ' · ' . HistoricoService::copiarActual($tabla) . ' filas base copiadas';
            }

            $this->line($linea);
        }

        return self::SUCCESS;
    }
}
