<?php

namespace App\Console\Commands;

use App\Models\Auditoria;
use App\Models\Documento;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;


class DocumentosHuerfanos extends Command
{
    protected $signature = 'documentos:huerfanos {--borrar : borra los archivos que ningun documento usa}';

    protected $description = 'Busca archivos en disco que no usa ningun documento (y documentos sin archivo)';


    public function handle(): int
    {
        $disco = Storage::disk('local');

        // los borrados suaves tambien cuentan, se pueden restaurar
        $usados = Documento::withTrashed()->whereNotNull('ruta')->pluck('ruta')->flip();

        $huerfanos = [];
        $bytes = 0;

        foreach ($disco->files('documentos') as $archivo) {

            // los que se subieron hace menos de un dia pueden estar por guardarse
            if ($usados->has($archivo) || $disco->lastModified($archivo) > now()->subDay()->timestamp) {
                continue;
            }

            $huerfanos[] = $archivo;
            $bytes += $disco->size($archivo);
        }

        $sinArchivo = Documento::withTrashed()->whereNotNull('ruta')->get()
            ->reject(fn (Documento $d) => $disco->exists($d->ruta));

        $this->table(
            ['Archivos sin documento', 'Ocupan (MB)', 'Documentos sin archivo'],
            [[count($huerfanos), round($bytes / 1048576, 2), $sinArchivo->count()]]
        );

        foreach ($sinArchivo as $documento) {
            $this->warn("Documento #{$documento->id} apunta a un archivo que no existe: {$documento->ruta}");
        }

        if ($this->option('borrar') && $huerfanos) {

            $disco->delete($huerfanos);

            Auditoria::registrar(
                evento: 'eliminado',
                descripcion: 'Borró ' . count($huerfanos) . ' archivos de documentos sin uso (' . round($bytes / 1048576, 2) . ' MB)',
                extra: ['archivos' => implode(', ', array_slice($huerfanos, 0, 20))],
                severidad: 'aviso',
            );

            $this->info('Borrados.');
        }

        return self::SUCCESS;
    }
}
