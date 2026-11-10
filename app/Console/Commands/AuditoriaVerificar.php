<?php

namespace App\Console\Commands;

use App\Models\Auditoria;
use Illuminate\Console\Command;


class AuditoriaVerificar extends Command
{
    protected $signature = 'auditoria:verificar';

    protected $description = 'Revisa que ningun registro de la auditoria fue modificado, borrado o agregado a mano';


    public function handle(): int
    {
        $resultado = Auditoria::verificarCadena();

        $this->line("Registros revisados: {$resultado['revisados']} (anteriores a la cadena: {$resultado['sin_hash']})");

        if ($resultado['ok']) {
            $this->info('La auditoría está íntegra.');

            return self::SUCCESS;
        }

        foreach ($resultado['problemas'] as $problema) {
            $this->error($problema);
        }

        Auditoria::registrar(
            evento: 'auditoria_alterada',
            descripcion: 'La verificación de la auditoría encontró ' . count($resultado['problemas']) . ' problema(s)',
            extra: ['problemas' => implode(' | ', array_slice($resultado['problemas'], 0, 5))],
        );

        return self::FAILURE;
    }
}
