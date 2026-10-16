<?php

namespace App\Console\Commands;

use App\Models\Auditoria;
use App\Models\Persona;
use App\Services\GeneradorDatosPrueba;
use App\Services\HabilitacionUsuarios;
use Illuminate\Console\Command;
use Illuminate\Support\Str;


class DatosPoblar extends Command
{
    protected $signature = 'datos:poblar
        {--personas=1000 : cantidad de personas (1 admin, 5 rrhh, 5 funcionarios y el resto empleados)}
        {--documentos=10 : documentos por persona (maximo 10)}
        {--password= : clave de todos los usuarios de prueba (si no, es al azar)}
        {--sin-archivos : no genera los archivos de los documentos}
        {--agregar : permite cargar aunque ya haya personas}
        {--si : no pregunta}';

    protected $description = 'Carga personas ficticias con legajo, familia, estudios, cursos, idiomas y documentos (para probar el sistema)';


    public function handle(): int
    {
        $personas = max(11, (int) $this->option('personas'));
        $documentos = min(10, max(0, (int) $this->option('documentos')));

        if (Persona::withTrashed()->exists() && ! $this->option('agregar')) {
            $this->error('Ya hay personas cargadas. Si querés sumar más, usá --agregar. Para empezar de cero: borrar_todo.sh');

            return self::FAILURE;
        }

        $clave = $this->option('password') ?: Str::password(14, symbols: false);

        $this->warn('Se van a crear datos FICTICIOS (no son personas reales).');
        $this->line("Personas: {$personas} | Documentos por persona: " . ($this->option('sin-archivos') ? 0 : $documentos));

        if (! $this->option('si') && ! $this->confirm('Seguir?', true)) {
            return self::SUCCESS;
        }

        $inicio = microtime(true);
        $barra = $this->output->createProgressBar($personas);
        $barra->start();
        $anterior = 0;

        $generador = new GeneradorDatosPrueba($personas, $documentos, $clave, ! $this->option('sin-archivos'));

        $resumen = $generador->ejecutar(function ($hechas) use ($barra, &$anterior) {
            $barra->advance($hechas - $anterior);
            $anterior = $hechas;
        });

        $barra->finish();
        $this->newLine(2);

        // el histórico guarda lo cargado y se aplica la regla de baja (los de baja quedan sin acceso)
        $this->call('historico:sincronizar', ['--copiar-actual' => true]);

        Persona::has('usuario')->with('usuario')->chunkById(200, function ($lote) {
            foreach ($lote as $persona) {
                HabilitacionUsuarios::sincronizar($persona);
            }
        });

        Auditoria::registrar(
            evento: 'importacion',
            descripcion: "Carga de datos de prueba: {$resumen['personas']} personas, {$resumen['documentos']} documentos",
            extra: array_map('strval', $resumen),
        );

        $segundos = round(microtime(true) - $inicio, 1);

        $this->table(['Dato', 'Cantidad'], collect($resumen)->except('bytes')->map(fn ($n, $k) => [$k, $n])->values()->all());
        $this->info("Documentos guardados (cifrados): " . round($resumen['bytes'] / 1048576, 1) . " MB. Tiempo: {$segundos} s.");

        $this->newLine();
        $this->warn('CUENTAS DE PRUEBA (misma clave para todas):');
        $this->table(['Rol', 'Correo', 'Nombre'], array_map(fn ($c) => [$c['rol'], $c['correo'], $c['nombre']], $generador->cuentas()));
        $this->line("Clave: {$clave}");
        $this->line('Las personas dadas de baja tienen el usuario deshabilitado (es la regla del sistema).');

        return self::SUCCESS;
    }
}
