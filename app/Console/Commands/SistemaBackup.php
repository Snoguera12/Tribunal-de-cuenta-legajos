<?php

namespace App\Console\Commands;

use App\Services\CopiaDeSeguridad;
use Illuminate\Console\Command;


class SistemaBackup extends Command
{
    protected $signature = 'sistema:backup {--tipo=completo : completo o db} {--sin-documentos}';

    protected $description = 'Hace un backup de las dos bases, los documentos y la configuracion';


    public function handle(CopiaDeSeguridad $copia): int
    {
        $tipo = $this->option('tipo');
        $conDocumentos = $tipo === 'completo' && ! $this->option('sin-documentos');

        if (! config('sistema.backup.password')) {
            $this->warn('BACKUP_ARCHIVE_PASSWORD no esta configurada: el zip no va a ir cifrado y no se incluye el .env');
        }

        try {
            $info = $copia->crear($tipo, $conDocumentos);
        } catch (\Throwable $e) {
            $this->error('Fallo: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info("Backup listo: {$info['nombre']} ({$info['archivos']} archivos, " . round($info['bytes'] / 1048576, 2) . " MB, {$info['segundos']} s)");

        return self::SUCCESS;
    }
}
