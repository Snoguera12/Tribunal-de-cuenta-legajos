<?php

namespace App\Console\Commands;

use App\Models\Auditoria;
use App\Models\Documento;
use App\Services\ArchivoSeguro;
use App\Services\OptimizadorDocumentos;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;


class DocumentosReoptimizar extends Command
{
    protected $signature = 'documentos:reoptimizar {--dry-run : solo calcula cuanto se ahorraria} {--sin-backup : no hace backup antes}';

    protected $description = 'Vuelve a comprimir los documentos ya guardados (jpeg y pdf) para ahorrar disco';


    public function handle(): int
    {
        $simular = (bool) $this->option('dry-run');

        if (! $simular && ! $this->option('sin-backup')) {
            $this->info('Haciendo backup antes de tocar los documentos...');
            $this->call('sistema:backup');
        }

        if (! $simular) {
            $this->warn('La reoptimizacion baja la calidad de las imagenes y no se puede deshacer (salvo con el backup).');

            if (! $this->confirm('Seguir?')) {
                return self::SUCCESS;
            }
        }

        $antes = $despues = $tocados = $saltados = 0;

        foreach (Documento::withTrashed()->whereNotNull('ruta')->get() as $documento) {

            $contenido = ArchivoSeguro::leer($documento->ruta);

            if ($contenido === null) {
                $saltados++;
                continue;
            }

            $peso = Storage::disk('local')->size($documento->ruta);
            $resultado = OptimizadorDocumentos::optimizar($contenido);

            // se compara contra lo que ocupa hoy en disco
            $nuevoPeso = strlen(ArchivoSeguro::encriptar($resultado['contenido']));

            $antes += $peso;

            if ($resultado['metodo'] !== 'original' && $nuevoPeso < $peso) {

                $despues += $nuevoPeso;
                $tocados++;

                if (! $simular) {
                    Storage::disk('local')->put($documento->ruta, ArchivoSeguro::encriptar($resultado['contenido']));
                }

            } else {

                $despues += $peso;
            }
        }

        $ahorro = $antes > 0 ? round(100 - $despues * 100 / $antes, 1) : 0;

        $this->table(
            ['Documentos optimizados', 'Sin cambios / sin archivo', 'Antes (MB)', 'Despues (MB)', 'Ahorro'],
            [[$tocados, $saltados, round($antes / 1048576, 2), round($despues / 1048576, 2), $ahorro . '%']]
        );

        if (! $simular) {
            Auditoria::registrar(
                evento: 'modificado',
                descripcion: "Reoptimizó {$tocados} documentos (ahorro {$ahorro}%)",
                extra: ['antes_bytes' => $antes, 'despues_bytes' => $despues],
                severidad: 'aviso',
            );
        }

        return self::SUCCESS;
    }
}
