<?php

namespace App\Console\Commands;

use App\Services\HistoricoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;


class HistoricoEnviar extends Command
{
    protected $signature = 'historico:enviar {--todo : vuelve a mandar TODO el historial al otro servidor (si perdió los datos)}';

    protected $description = 'Manda al otro servidor el historial que todavía no llegó (o todo, con --todo)';


    public function handle(): int
    {
        // si el otro servidor falló hace un momento, acá se vuelve a intentar igual
        HistoricoService::olvidarCaida();

        foreach (array_keys(HistoricoService::modelos()) as $tabla) {

            try {

                if ($this->option('todo')) {
                    HistoricoService::marcarTodoPendiente($tabla);
                }

                [$enviadas, $quedan] = HistoricoService::enviarPendientes($tabla);

            } catch (\Throwable $e) {

                $this->error('El otro servidor no responde: ' . mb_strimwidth($e->getMessage(), 0, 200, '…'));
                $this->line('Lo que falta queda pendiente y se manda la próxima vez.');

                Log::warning('historico:enviar no pudo llegar al otro servidor: ' . $e->getMessage());

                return self::FAILURE;
            }

            $this->line(HistoricoService::nombre($tabla) . ': ' . $enviadas . ' enviadas, ' . $quedan . ' pendientes');
        }

        return self::SUCCESS;
    }
}
