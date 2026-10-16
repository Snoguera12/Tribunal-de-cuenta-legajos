<?php

namespace App\Console\Commands;

use App\Models\Auditoria;
use App\Services\HistoricoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;


class HistoricoRestaurar extends Command
{
    protected $signature = 'historico:restaurar {tabla} {id} {version : numero de version del historico} {--si : no pregunta}';

    protected $description = 'Vuelve un registro a una version vieja guardada en el historico';


    public function handle(): int
    {
        $tabla = $this->argument('tabla');
        $id = $this->argument('id');

        $version = HistoricoService::versiones($tabla, $id)
            ->firstWhere('hist_version', (int) $this->argument('version'));

        if (! $version) {
            $this->error('No existe esa version.');
            return self::FAILURE;
        }

        $datos = collect((array) $version)
            ->reject(fn ($v, $campo) => str_starts_with($campo, 'hist_'))
            ->all();

        $this->table(['campo', 'valor'], collect($datos)->map(fn ($v, $k) => [$k, (string) $v])->values()->all());

        if (! $this->option('si') && ! $this->confirm('Restaurar este registro?')) {
            return self::SUCCESS;
        }

        $actual = DB::table($tabla)->where('id', $id)->first();

        // si estaba borrado (borrado suave) lo vuelve a dejar activo
        if (array_key_exists('deleted_at', $datos)) {
            $datos['deleted_at'] = null;
        }

        if ($actual) {
            DB::table($tabla)->where('id', $id)->update($datos);
        } else {
            DB::table($tabla)->insert($datos);
        }

        Auditoria::registrar(
            evento: 'modificado',
            descripcion: "Restauró {$tabla} #{$id} a la versión {$this->argument('version')} del histórico",
            antes: $actual ? (array) $actual : null,
            despues: $datos,
            extra: ['tabla' => $tabla, 'version' => $this->argument('version')],
            severidad: 'alta',
        );

        $this->info('Listo.');

        return self::SUCCESS;
    }
}
