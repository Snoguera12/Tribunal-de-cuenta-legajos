<?php

namespace App\Console\Commands;

use App\Models\Documento;
use App\Services\ArchivoSeguro;
use App\Services\OptimizadorDocumentos;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

// los documentos subidos antes de ArchivoSeguro están guardados sin cifrar: esto los cifra en el mismo lugar
class DocumentosCifrarExistentes extends Command
{
    protected $signature = 'documentos:cifrar-existentes
        {--simular : solo muestra qué haría, no cambia nada}
        {--optimizar : además recomprime los PDF/JPG antes de cifrarlos}';

    protected $description = 'Cifra (y opcionalmente recomprime) los documentos que se guardaron sin cifrar';

    public function handle(): int
    {
        $simular = (bool) $this->option('simular');
        $optimizar = (bool) $this->option('optimizar');

        $ya = $migrados = $faltan = $errores = 0;
        $bytesAntes = $bytesDespues = 0;
        $vistos = [];

        foreach (Documento::query()->whereNotNull('ruta')->cursor() as $documento) {

            // varios documentos pueden apuntar al mismo archivo
            if (isset($vistos[$documento->ruta])) {
                continue;
            }

            $vistos[$documento->ruta] = true;

            if (! Storage::disk('local')->exists($documento->ruta)) {
                $faltan++;
                continue;
            }

            $crudo = Storage::disk('local')->get($documento->ruta);

            if (ArchivoSeguro::desencriptar($crudo) !== null) {
                $ya++;
                continue;
            }

            if (! ArchivoSeguro::esPdfOJpeg($crudo)) {
                $errores++;
                $this->warn("Documento #{$documento->id}: no parece PDF/JPG, se saltea ({$documento->ruta})");
                continue;
            }

            $contenido = $optimizar ? OptimizadorDocumentos::optimizar($crudo)['contenido'] : $crudo;
            $nuevo = ArchivoSeguro::encriptar($contenido);

            $bytesAntes += strlen($crudo);
            $bytesDespues += strlen($nuevo);

            if (! $simular) {
                Storage::disk('local')->put($documento->ruta, $nuevo);

                // se comprueba que se puede volver a leer antes de seguir con el próximo
                if (ArchivoSeguro::leer($documento->ruta) !== $contenido) {
                    Storage::disk('local')->put($documento->ruta, $crudo);
                    $errores++;
                    $this->error("Documento #{$documento->id}: no se pudo verificar el cifrado, se restauró el original");
                    continue;
                }
            }

            $migrados++;
        }

        $this->table(['Ya cifrados', 'Cifrados', 'Sin archivo', 'Con error', 'KB antes', 'KB después'],
            [[$ya, $migrados, $faltan, $errores, round($bytesAntes / 1024), round($bytesDespues / 1024)]]);

        if ($simular) {
            $this->comment('Simulación: no se modificó nada.');
        }

        return $errores > 0 ? self::FAILURE : self::SUCCESS;
    }
}
