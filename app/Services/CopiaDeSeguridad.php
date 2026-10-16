<?php

namespace App\Services;

use App\Models\Auditoria;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use ZipArchive;


// backup: las dos bases (binaria + json), documentos, config y manifiesto con hash de cada archivo
class CopiaDeSeguridad
{

    // tablas que no vale la pena guardar
    private const IGNORAR_TABLAS = ['sessions', 'cache', 'cache_locks'];


    public function conexiones(): array
    {
        return array_values(array_unique([config('database.default'), 'historico']));
    }


    public function discos(): array
    {
        return config('sistema.backup.discos') ?: ['backups'];
    }


    public function crear(string $tipo = 'completo', bool $conDocumentos = true): array
    {
        $inicio = microtime(true);
        $marca = now()->format('Ymd_His');
        $nombre = "{$tipo}_{$marca}.zip";
        $temp = storage_path('app/backup-temp/' . $marca . '_' . bin2hex(random_bytes(3)));

        File::ensureDirectoryExists($temp);

        try {

            $archivos = [];
            $bases = [];

            foreach ($this->conexiones() as $conexion) {
                $bases[$conexion] = $this->copiarBase($conexion, $temp, $archivos);
            }

            if ($conDocumentos) {
                $this->juntarDocumentos($archivos);
            }

            $this->juntarCodigo($archivos);

            $manifiesto = $this->armarManifiesto($tipo, $bases, $archivos, $conDocumentos);
            $zip = $this->armarZip($temp . '/' . $nombre, $archivos, $manifiesto);

            $revision = $this->verificar($zip);

            if (! $revision['ok']) {
                throw new \RuntimeException('El backup recién hecho no pasó la verificación: ' . implode(' | ', $revision['errores']));
            }

            $this->guardarEnDiscos($zip, $nombre, $manifiesto);

            $info = [
                'nombre' => $nombre,
                'bytes' => filesize($zip),
                'segundos' => round(microtime(true) - $inicio, 1),
                'archivos' => count($archivos),
                'cifrado' => $manifiesto['cifrado'],
            ];

            Auditoria::registrar(
                evento: 'backup_ok',
                descripcion: "Backup {$tipo} creado: {$nombre}",
                extra: $info + ['discos' => implode(',', $this->discos())],
            );

            return $info;

        } catch (\Throwable $e) {

            Auditoria::registrar(
                evento: 'backup_fallo',
                descripcion: 'Falló el backup ' . $tipo . ': ' . mb_substr($e->getMessage(), 0, 250),
            );

            throw $e;

        } finally {

            File::deleteDirectory($temp);
        }
    }


    // ---------------------------------------------------------- bases de datos

    private function copiarBase(string $conexion, string $temp, array &$archivos): array
    {
        $config = config("database.connections.{$conexion}");
        $driver = $config['driver'];
        $resumen = ['driver' => $driver, 'tablas' => []];

        File::ensureDirectoryExists($temp . '/db');

        if ($driver === 'sqlite') {

            $destino = $temp . "/db/{$conexion}.sqlite";

            if ($config['database'] !== ':memory:') {
                try {
                    DB::connection($conexion)->statement("VACUUM INTO '" . str_replace("'", "''", $destino) . "'");
                } catch (\Throwable $e) {
                    copy($config['database'], $destino);
                }

                $archivos["db/{$conexion}.sqlite"] = $destino;
            }

        } elseif ($driver === 'mysql' || $driver === 'mariadb') {

            $destino = $temp . "/db/{$conexion}.sql";

            $resultado = Process::timeout(600)
                ->env(['MYSQL_PWD' => (string) $config['password']])
                ->run([
                    'mysqldump', '--single-transaction', '--routines', '--triggers',
                    '-h', $config['host'], '-P', (string) $config['port'], '-u', $config['username'],
                    $config['database'],
                ]);

            if (! $resultado->successful()) {
                throw new \RuntimeException('mysqldump falló: ' . mb_substr($resultado->errorOutput(), 0, 200));
            }

            file_put_contents($destino, $resultado->output());
            $archivos["db/{$conexion}.sql"] = $destino;
        }

        // exportacion logica, sirve aunque cambie el motor de base
        foreach (Schema::connection($conexion)->getTableListing(null, false) as $tabla) {

            $tabla = str_contains($tabla, '.') ? explode('.', $tabla)[1] : $tabla;

            if (in_array($tabla, self::IGNORAR_TABLAS) || str_starts_with($tabla, 'sqlite_')) {
                continue;
            }

            $ruta = $temp . "/logico/{$conexion}/{$tabla}.jsonl";
            File::ensureDirectoryExists(dirname($ruta));

            $f = fopen($ruta, 'w');
            $filas = 0;

            DB::connection($conexion)->table($tabla)->orderBy(DB::raw('1'))->chunk(500, function ($lote) use ($f, &$filas) {
                foreach ($lote as $fila) {
                    fwrite($f, json_encode($fila, JSON_UNESCAPED_UNICODE) . "\n");
                    $filas++;
                }
            });

            fclose($f);

            $archivos["logico/{$conexion}/{$tabla}.jsonl"] = $ruta;
            $resumen['tablas'][$tabla] = $filas;
        }

        return $resumen;
    }


    // ---------------------------------------------------------- documentos y codigo

    private function juntarDocumentos(array &$archivos): void
    {
        foreach (['private' => storage_path('app/private'), 'public' => storage_path('app/public')] as $nombre => $base) {

            if (! is_dir($base)) {
                continue;
            }

            foreach (File::allFiles($base, true) as $archivo) {

                $relativa = str_replace('\\', '/', $archivo->getRelativePathname());

                if (str_starts_with($relativa, 'livewire-tmp') || str_starts_with($relativa, '.gitignore')) {
                    continue;
                }

                $archivos["archivos/{$nombre}/{$relativa}"] = $archivo->getRealPath();
            }
        }
    }


    private function juntarCodigo(array &$archivos): void
    {
        foreach (['composer.json', 'composer.lock'] as $nombre) {
            if (is_file(base_path($nombre))) {
                $archivos["sistema/{$nombre}"] = base_path($nombre);
            }
        }

        foreach (File::glob(base_path('config/*.php')) as $ruta) {
            $archivos['sistema/config/' . basename($ruta)] = $ruta;
        }

        // el .env tiene el APP_KEY, sin esa clave los documentos cifrados no se pueden leer
        if (config('sistema.backup.incluir_env') && config('sistema.backup.password') && is_file(base_path('.env'))) {
            $archivos['sistema/.env'] = base_path('.env');
        }
    }


    // ---------------------------------------------------------- manifiesto y zip

    private function armarManifiesto(string $tipo, array $bases, array $archivos, bool $conDocumentos): array
    {
        $hashes = [];
        $bytes = 0;

        foreach ($archivos as $dentro => $ruta) {
            $hashes[$dentro] = hash_file('sha256', $ruta);
            $bytes += filesize($ruta);
        }

        $commit = null;
        try {
            $git = Process::timeout(3)->path(base_path())->run(['git', 'rev-parse', 'HEAD']);
            $commit = $git->successful() ? trim($git->output()) : null;
        } catch (\Throwable $e) {
        }

        return [
            'version' => 2,
            'tipo' => $tipo,
            'creado' => now()->toIso8601String(),
            'servidor' => gethostname(),
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'entorno' => config('app.env'),
            'commit' => $commit,
            'con_documentos' => $conDocumentos,
            'cifrado' => (bool) config('sistema.backup.password'),
            'incluye_env' => isset($archivos['sistema/.env']),
            'huella_app_key' => substr(hash('sha256', (string) config('app.key')), 0, 12),
            'auditoria' => $this->resumenAuditoria(),
            'bases' => $bases,
            'total_archivos' => count($archivos),
            'total_bytes' => $bytes,
            'archivos' => $hashes,
        ];
    }


    // ultimo registro de la auditoria: sirve para comprobar despues que no falta nada
    private function resumenAuditoria(): array
    {
        $ultimo = Auditoria::query()->whereNotNull('hash')->orderByDesc('id')->first(['id', 'hash']);

        return ['total' => Auditoria::count(), 'ultimo_id' => $ultimo?->id, 'ultimo_hash' => $ultimo?->hash];
    }


    private function armarZip(string $destino, array $archivos, array $manifiesto): string
    {
        $zip = new ZipArchive();

        if ($zip->open($destino, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('No se pudo crear el zip');
        }

        $clave = config('sistema.backup.password');

        if ($clave) {
            $zip->setPassword($clave);
        }

        $zip->addFromString('manifest.json', json_encode($manifiesto, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        foreach ($archivos as $dentro => $ruta) {
            $zip->addFile($ruta, $dentro);
        }

        if ($clave) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $zip->setEncryptionIndex($i, ZipArchive::EM_AES_256);
            }
        }

        if (! $zip->close()) {
            throw new \RuntimeException('No se pudo cerrar el zip');
        }

        return $destino;
    }


    private function guardarEnDiscos(string $zip, string $nombre, array $manifiesto): void
    {
        $hash = hash_file('sha256', $zip);

        $resumen = [
            'nombre' => $nombre,
            'sha256' => $hash,
            'tipo' => $manifiesto['tipo'],
            'creado' => $manifiesto['creado'],
            'cifrado' => $manifiesto['cifrado'],
            'bytes' => filesize($zip),
            'tablas' => collect($manifiesto['bases'])->map(fn ($b) => array_sum($b['tablas']))->all(),
        ];

        foreach ($this->discos() as $disco) {

            $stream = fopen($zip, 'r');
            Storage::disk($disco)->writeStream($nombre, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }

            Storage::disk($disco)->put($nombre . '.sha256', $hash . '  ' . $nombre . "\n");
            Storage::disk($disco)->put($nombre . '.json', json_encode($resumen, JSON_PRETTY_PRINT));
        }
    }


    // ---------------------------------------------------------- verificar

    // abre el zip, compara el hash de cada archivo y revisa las bases sqlite
    public function verificar(string $zipRuta): array
    {
        $errores = [];
        $zip = new ZipArchive();

        if ($zip->open($zipRuta) !== true) {
            return ['ok' => false, 'errores' => ['no se pudo abrir el zip'], 'archivos' => 0];
        }

        if (config('sistema.backup.password')) {
            $zip->setPassword(config('sistema.backup.password'));
        }

        $json = $zip->getFromName('manifest.json');
        $manifiesto = $json ? json_decode($json, true) : null;

        if (! $manifiesto) {
            $zip->close();
            return ['ok' => false, 'errores' => ['no se pudo leer el manifiesto (clave incorrecta o zip dañado)'], 'archivos' => 0];
        }

        foreach ($manifiesto['archivos'] as $dentro => $hashEsperado) {

            $stream = $zip->getStream($dentro);

            if (! $stream) {
                $errores[] = "falta {$dentro}";
                continue;
            }

            $ctx = hash_init('sha256');
            hash_update_stream($ctx, $stream);
            fclose($stream);

            if (! hash_equals($hashEsperado, hash_final($ctx))) {
                $errores[] = "hash distinto en {$dentro}";
            }
        }

        // integridad de las bases sqlite
        foreach ($manifiesto['bases'] as $conexion => $base) {

            if ($base['driver'] !== 'sqlite' || ! isset($manifiesto['archivos']["db/{$conexion}.sqlite"])) {
                continue;
            }

            $tmp = tempnam(sys_get_temp_dir(), 'bk');
            file_put_contents($tmp, $zip->getFromName("db/{$conexion}.sqlite"));

            try {
                $pdo = new \PDO('sqlite:' . $tmp);
                $estado = $pdo->query('PRAGMA integrity_check')->fetchColumn();

                if ($estado !== 'ok') {
                    $errores[] = "la base {$conexion} está dañada ({$estado})";
                }

                foreach ($base['tablas'] as $tabla => $filas) {
                    $reales = (int) $pdo->query('SELECT COUNT(*) FROM "' . $tabla . '"')->fetchColumn();
                    if ($reales !== $filas) {
                        $errores[] = "{$conexion}.{$tabla}: tiene {$reales} filas y el manifiesto dice {$filas}";
                    }
                }
            } catch (\Throwable $e) {
                $errores[] = "no se pudo abrir la base {$conexion}: " . $e->getMessage();
            }

            $pdo = null;
            @unlink($tmp);
        }

        $zip->close();

        return ['ok' => empty($errores), 'errores' => $errores, 'archivos' => count($manifiesto['archivos']), 'manifiesto' => $manifiesto];
    }


    // ---------------------------------------------------------- listar y limpiar

    public function listar(?string $disco = null): array
    {
        $disco ??= $this->discos()[0];
        $lista = [];

        foreach (Storage::disk($disco)->files() as $archivo) {

            if (! str_ends_with($archivo, '.zip')) {
                continue;
            }

            [$tipo] = explode('_', $archivo, 2);
            $momento = \Carbon\Carbon::createFromTimestamp(Storage::disk($disco)->lastModified($archivo));

            if (preg_match('/_(\d{8})_(\d{6})\.zip$/', $archivo, $m)) {
                $momento = \Carbon\Carbon::createFromFormat('Ymd His', $m[1] . ' ' . $m[2]);
            }

            $lista[] = [
                'nombre' => $archivo,
                'tipo' => $tipo,
                'fecha' => $momento,
                'bytes' => Storage::disk($disco)->size($archivo),
                'tiene_hash' => Storage::disk($disco)->exists($archivo . '.sha256'),
            ];
        }

        usort($lista, fn ($a, $b) => $b['fecha'] <=> $a['fecha']);

        return $lista;
    }


    // chequea que el archivo guardado no cambio desde que se hizo
    public function hashCorrecto(string $nombre, ?string $disco = null): bool
    {
        $disco ??= $this->discos()[0];

        if (! Storage::disk($disco)->exists($nombre . '.sha256')) {
            return false;
        }

        $esperado = explode(' ', trim(Storage::disk($disco)->get($nombre . '.sha256')))[0];
        $ctx = hash_init('sha256');
        $stream = Storage::disk($disco)->readStream($nombre);
        hash_update_stream($ctx, $stream);
        fclose($stream);

        return hash_equals($esperado, hash_final($ctx));
    }


    // devuelve el zip en una ruta local para poder abrirlo
    public function rutaLocal(string $nombre, ?string $disco = null): string
    {
        $disco ??= $this->discos()[0];
        $tmp = tempnam(sys_get_temp_dir(), 'bkz');
        $salida = fopen($tmp, 'w');
        stream_copy_to_stream(Storage::disk($disco)->readStream($nombre), $salida);
        fclose($salida);

        return $tmp;
    }


    /*
     * que backups se quedan: todos los de los ultimos dias, uno por dia hasta
     * el mes, uno por semana hasta las semanas configuradas y uno por mes despues.
     * El mas nuevo nunca se borra.
     */
    public function aBorrar(array $lista, ?\Carbon\Carbon $ahora = null): array
    {
        $ahora ??= now();
        $r = config('sistema.backup.retencion');
        $guardar = [];
        $vistos = [];

        // se agrupa por tipo (completo, db, ...)
        $porTipo = collect($lista)->groupBy('tipo');

        foreach ($porTipo as $tipo => $items) {

            foreach ($items->sortByDesc('fecha')->values() as $i => $item) {

                $dias = $item['fecha']->diffInDays($ahora);

                if ($i === 0 || $dias < $r['todos_dias']) {
                    $guardar[$item['nombre']] = true;
                    continue;
                }

                if ($dias < $r['diarios_dias']) {
                    $clave = $tipo . 'd' . $item['fecha']->format('Y-m-d');
                } elseif ($dias < $r['semanales_semanas'] * 7) {
                    $clave = $tipo . 's' . $item['fecha']->format('o-W');
                } elseif ($dias < $r['mensuales_meses'] * 31) {
                    $clave = $tipo . 'm' . $item['fecha']->format('Y-m');
                } else {
                    continue;
                }

                if (! isset($vistos[$clave])) {
                    $vistos[$clave] = true;
                    $guardar[$item['nombre']] = true;
                }
            }
        }

        return collect($lista)->pluck('nombre')->reject(fn ($n) => isset($guardar[$n]))->values()->all();
    }


    public function limpiar(): array
    {
        $borrados = [];

        foreach ($this->discos() as $disco) {
            foreach ($this->aBorrar($this->listar($disco)) as $nombre) {
                Storage::disk($disco)->delete([$nombre, $nombre . '.sha256', $nombre . '.json']);
                $borrados[] = "{$disco}:{$nombre}";
            }
        }

        return $borrados;
    }


    // ---------------------------------------------------------- restaurar

    // restaura bases sqlite y/o documentos. Antes hace un backup de lo que hay
    public function restaurar(string $nombre, bool $bases = true, bool $documentos = true, ?string $disco = null): array
    {
        $local = $this->rutaLocal($nombre, $disco);

        try {

            $revision = $this->verificar($local);

            if (! $revision['ok']) {
                throw new \RuntimeException('El backup no pasó la verificación: ' . implode(' | ', $revision['errores']));
            }

            $this->crear('prerestauracion', true);

            $zip = new ZipArchive();
            $zip->open($local);

            if (config('sistema.backup.password')) {
                $zip->setPassword(config('sistema.backup.password'));
            }

            $hecho = [];

            if ($bases) {
                foreach ($revision['manifiesto']['bases'] as $conexion => $base) {

                    if ($base['driver'] !== 'sqlite') {
                        $hecho[] = "{$conexion}: es {$base['driver']}, restaurar a mano con el .sql del backup";
                        continue;
                    }

                    $destino = config("database.connections.{$conexion}.database");

                    DB::purge($conexion);
                    file_put_contents($destino, $zip->getFromName("db/{$conexion}.sqlite"));
                    $hecho[] = "{$conexion}: base restaurada";
                }
            }

            if ($documentos) {
                $cantidad = 0;
                $raiz = [
                    'archivos/private/' => storage_path('app/private'),
                    'archivos/public/' => storage_path('app/public'),
                ];

                for ($i = 0; $i < $zip->numFiles; $i++) {

                    $dentro = $zip->getNameIndex($i);

                    foreach ($raiz as $prefijo => $carpeta) {

                        if (! str_starts_with($dentro, $prefijo)) {
                            continue;
                        }

                        $relativa = substr($dentro, strlen($prefijo));

                        // evita que un zip armado a mano escriba fuera de storage
                        if (str_contains($relativa, '..') || str_starts_with($relativa, '/')) {
                            continue;
                        }

                        $ruta = $carpeta . '/' . $relativa;
                        File::ensureDirectoryExists(dirname($ruta));
                        file_put_contents($ruta, $zip->getFromIndex($i));
                        $cantidad++;
                    }
                }

                $hecho[] = "documentos restaurados: {$cantidad}";
            }

            $zip->close();

            Auditoria::registrar(
                evento: 'backup_restaurado',
                descripcion: "Se restauró el backup {$nombre}",
                extra: ['detalle' => implode(' | ', $hecho)],
            );

            return $hecho;

        } finally {

            @unlink($local);
        }
    }
}
