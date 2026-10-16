<?php

namespace App\Console\Commands;

use App\Models\Documento;
use App\Services\ArchivoSeguro;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;


class EncriptarDocumentosExistentes extends Command
{
    protected $signature = 'documentos:encriptar-existentes {--dry-run : solo muestra que haria}';

    protected $description = 'Comprime y cifra los documentos que se subieron antes de ArchivoSeguro';


    public function handle(): int
    {
        $simular = (bool) $this->option('dry-run');

        $ya = $migrados = $faltan = $errores = 0;

        // withTrashed para no dejar sin cifrar los que estan en la papelera
        foreach (Documento::withTrashed()->whereNotNull('ruta')->get() as $documento) {

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
                $this->warn("Documento #{$documento->id}: no parece pdf/jpg, se saltea");
                continue;
            }

            if (! $simular) {
                Storage::disk('local')->put($documento->ruta, ArchivoSeguro::encriptar($crudo));
            }

            $migrados++;
        }

        $this->table(['Ya cifrados', 'Migrados', 'Sin archivo', 'Con error'], [[$ya, $migrados, $faltan, $errores]]);

        if ($simular) {
            $this->comment('Simulacion, no se modifico nada.');
        }

        return self::SUCCESS;
    }
}
