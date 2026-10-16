<?php

namespace App\Console\Commands;

use App\Models\Auditoria;
use App\Services\CopiaDeSeguridad;
use Illuminate\Console\Command;


class SistemaBackupVerificar extends Command
{
    protected $signature = 'sistema:backup-verificar {archivo? : si no se pone, el mas nuevo}';

    protected $description = 'Revisa que el ultimo backup exista, sea reciente y no este dañado';


    public function handle(CopiaDeSeguridad $copia): int
    {
        $lista = $copia->listar();
        $nombre = $this->argument('archivo') ?? ($lista[0]['nombre'] ?? null);

        if (! $nombre) {
            Auditoria::registrar('backup_viejo', 'No existe ningún backup');
            $this->error('No hay backups.');
            return self::FAILURE;
        }

        $item = collect($lista)->firstWhere('nombre', $nombre);

        // muy viejo
        if ($item && $item['fecha']->diffInHours(now()) > config('sistema.backup.horas_maximas') && ! $this->argument('archivo')) {
            Auditoria::registrar('backup_viejo', 'El último backup es de ' . $item['fecha']->format('d/m/Y H:i'));
            $this->error('El ultimo backup es muy viejo.');
            return self::FAILURE;
        }

        if (! $copia->hashCorrecto($nombre)) {
            Auditoria::registrar('backup_fallo', "El backup {$nombre} cambió desde que se hizo (hash distinto)");
            $this->error('El hash no coincide.');
            return self::FAILURE;
        }

        $local = $copia->rutaLocal($nombre);
        $resultado = $copia->verificar($local);
        @unlink($local);

        if (! $resultado['ok']) {
            Auditoria::registrar('backup_fallo', "El backup {$nombre} no pasó la verificación: " . implode(' | ', $resultado['errores']));
            $this->error(implode("\n", $resultado['errores']));
            return self::FAILURE;
        }

        Auditoria::registrar('backup_verificado', "Backup {$nombre} verificado ({$resultado['archivos']} archivos)");
        $this->info("OK: {$nombre} ({$resultado['archivos']} archivos verificados)");

        return self::SUCCESS;
    }
}
