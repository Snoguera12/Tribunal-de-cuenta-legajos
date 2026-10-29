<?php

namespace App\Console\Commands;

use App\Models\Auditoria;
use App\Models\AuditoriaArchivo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;


class AuditoriaArchivar extends Command
{
    protected $signature = 'auditoria:archivar {--dias=365 : lo mas viejo que esto se archiva} {--si : no pregunta}';

    protected $description = 'Pasa la auditoria vieja a un archivo comprimido (la cadena de hashes sigue verificando)';


    public function handle(): int
    {
        $corte = now()->subDays((int) $this->option('dias'))->format('Y-m-d H:i:s');

        // no se archiva si la cadena ya esta rota
        $estado = Auditoria::verificarCadena();

        if (! $estado['ok']) {
            $this->error('La cadena tiene problemas, no se archiva nada. Corré auditoria:verificar.');

            return self::FAILURE;
        }

        // se archiva siempre desde el principio hasta un punto (sino la cadena se corta)
        $hastaId = Auditoria::where('created_at', '<', $corte)->max('id');

        if (! $hastaId) {
            $this->info('No hay registros tan viejos.');

            return self::SUCCESS;
        }

        $cantidad = Auditoria::where('id', '<=', $hastaId)->count();

        if (! $this->option('si') && ! $this->confirm("Se archivan {$cantidad} registros (hasta el #{$hastaId}). Seguir?")) {
            return self::SUCCESS;
        }

        $ultimo = Auditoria::where('id', '<=', $hastaId)->whereNotNull('hash')->orderByDesc('id')->first(['id', 'hash']);
        $desdeId = Auditoria::min('id');

        $temp = tempnam(sys_get_temp_dir(), 'aud') . '.gz';
        $salida = gzopen($temp, 'wb9');

        DB::connection('historico')->table('auditorias')->where('id', '<=', $hastaId)->orderBy('id')
            ->chunk(500, function ($filas) use ($salida) {
                foreach ($filas as $fila) {
                    gzwrite($salida, json_encode($fila, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
                }
            });

        gzclose($salida);

        $nombre = "auditoria/auditoria_{$desdeId}_{$hastaId}.jsonl.gz";
        $sha = hash_file('sha256', $temp);

        foreach (config('sistema.backup.discos') ?: ['backups'] as $disco) {
            Storage::disk($disco)->writeStream($nombre, fopen($temp, 'r'));
            Storage::disk($disco)->put($nombre . '.sha256', $sha . '  ' . basename($nombre) . "\n");
        }

        File::delete($temp);

        // recien ahora que el archivo esta guardado se borra de la base
        DB::connection('historico')->transaction(function () use ($desdeId, $hastaId, $cantidad, $ultimo, $nombre, $sha) {

            AuditoriaArchivo::create([
                'desde_id' => $desdeId, 'hasta_id' => $hastaId, 'cantidad' => $cantidad,
                'ultimo_hash' => $ultimo?->hash ?? str_repeat('0', 64), 'archivo' => $nombre, 'sha256' => $sha,
            ]);

            DB::connection('historico')->table('auditorias')->where('id', '<=', $hastaId)->delete();
        });

        Auditoria::registrar(
            evento: 'auditoria_archivada',
            descripcion: "Se archivaron {$cantidad} registros de auditoría (#{$desdeId} a #{$hastaId})",
            extra: ['archivo' => $nombre, 'sha256' => $sha],
        );

        $this->info("Archivados {$cantidad} registros en {$nombre}");

        return self::SUCCESS;
    }
}
