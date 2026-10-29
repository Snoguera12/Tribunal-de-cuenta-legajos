<?php

namespace App\Console\Commands;

use App\Models\Auditoria;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Deja el sistema como recien instalado: vacia la base principal Y el historico/auditoria,
 * y borra los documentos del disco (migrate:fresh solo hace lo primero y dejaba archivos huerfanos).
 */
class SistemaReiniciar extends Command
{
    protected $signature = 'sistema:reiniciar
        {--si : no pregunta}
        {--sin-catalogos : no carga areas, cargos y categorias}
        {--borrar-backups : tambien borra las copias de seguridad}
        {--forzar-produccion : permite correrlo con APP_ENV=production}';

    protected $description = 'Vacia bases, historico, auditoria y documentos (queda como recien instalado)';


    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('forzar-produccion')) {
            $this->error('El entorno es PRODUCCION: no se reinicia. Si de verdad querés hacerlo, agregá --forzar-produccion.');

            return self::FAILURE;
        }

        $this->warn('Esto BORRA todas las personas, usuarios, la auditoría, el historial y los documentos. No se puede deshacer.');

        if (! $this->option('si') && $this->ask('Escribí REINICIAR para confirmar') !== 'REINICIAR') {
            $this->line('No se borró nada.');

            return self::SUCCESS;
        }

        Auditoria::sinRegistrar(function () {
            // el historico es otra base: migrate:fresh no la toca
            Schema::connection('historico')->dropAllTables();

            Artisan::call('migrate:fresh', ['--force' => true]);
        });

        $disco = Storage::disk('local');
        $archivos = count($disco->allFiles('documentos'));
        $disco->deleteDirectory('documentos');
        $disco->makeDirectory('documentos');
        $disco->delete('auditoria_ancla.json');

        if ($this->option('borrar-backups')) {
            foreach (['backups', 'backups_externo', 'backup-temp'] as $carpeta) {
                File::cleanDirectory(storage_path("app/{$carpeta}")) ;
            }
        }

        if (! $this->option('sin-catalogos')) {
            Artisan::call('db:seed', ['--class' => CatalogosSeeder::class, '--force' => true]);
        }

        $this->info("Listo: bases vaciadas, {$archivos} documento(s) borrado(s)" . ($this->option('sin-catalogos') ? '.' : ' y catálogos reales cargados.'));
        $this->line('Para entrar:  php artisan sistema:crear-admin correo@dominio --sector=tic');

        return self::SUCCESS;
    }
}
