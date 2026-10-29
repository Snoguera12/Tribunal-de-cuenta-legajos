<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Revisa que el servidor este listo (lista de verificacion de docs/PUESTA_EN_PRODUCCION.md).
 * Cada punto sale como OK, AVISO (conviene arreglarlo) o ERROR (hay que arreglarlo). Con algun ERROR termina con codigo 1.
 */
class SistemaDiagnostico extends Command
{
    protected $signature = 'sistema:diagnostico';

    protected $description = 'Revisa la configuracion del servidor: entorno, bases, Ghostscript, backups, cron y claves de ejemplo';

    private array $filas = [];


    public function handle(): int
    {
        $this->filas = [];

        $this->entorno();
        $this->bases();
        $this->herramientas();
        $this->respaldos();
        $this->programador();
        $this->cuentas();

        $this->table(['Estado', 'Punto', 'Detalle'], array_map(fn ($f) => [
            match ($f[0]) { 'OK' => '<info>OK</info>', 'AVISO' => '<comment>AVISO</comment>', default => '<error>ERROR</error>' }, $f[1], $f[2],
        ], $this->filas));

        $errores = count(array_filter($this->filas, fn ($f) => $f[0] === 'ERROR'));
        $avisos = count(array_filter($this->filas, fn ($f) => $f[0] === 'AVISO'));

        $this->line("Resultado: {$errores} error(es), {$avisos} aviso(s).");

        return $errores > 0 ? self::FAILURE : self::SUCCESS;
    }


    private function anotar(string $estado, string $punto, string $detalle): void
    {
        $this->filas[] = [$estado, $punto, $detalle];
    }


    private function entorno(): void
    {
        $produccion = app()->environment('production');

        $this->anotar('OK', 'Entorno', 'APP_ENV=' . app()->environment());

        if (config('app.debug')) {
            $this->anotar($produccion ? 'ERROR' : 'AVISO', 'APP_DEBUG', $produccion ? 'está en true en producción: muestra errores y datos internos a cualquiera' : 'está en true (en producción debe ser false)');
        } else {
            $this->anotar('OK', 'APP_DEBUG', 'false');
        }

        $this->anotar(config('app.key') ? 'OK' : 'ERROR', 'APP_KEY', config('app.key') ? 'definida (sin ella no se abren los documentos cifrados)' : 'falta: php artisan key:generate');

        if ($produccion && str_starts_with((string) config('app.url'), 'https') && ! env('TRUSTED_PROXIES')) {
            $this->anotar('AVISO', 'TRUSTED_PROXIES', 'si hay un proxy delante, definilo para registrar la IP real');
        }

        if (is_file(base_path('.env')) && (fileperms(base_path('.env')) & 0004)) {
            $this->anotar('AVISO', 'Permisos del .env', 'cualquier usuario del servidor puede leerlo: chmod 600 .env');
        }
    }


    private function bases(): void
    {
        foreach ([config('database.default'), 'historico'] as $conexion) {
            $driver = config("database.connections.{$conexion}.driver");
            $etiqueta = $conexion === 'historico' ? 'Base histórico/auditoría' : 'Base principal';

            try {
                DB::connection($conexion)->getPdo();
            } catch (Throwable $e) {
                $this->anotar('ERROR', $etiqueta, 'no conecta: ' . $e->getMessage());
                continue;
            }

            if ($driver !== 'sqlite') {
                $this->anotar('OK', $etiqueta, $driver);
                continue;
            }

            $modo = strtolower((string) (DB::connection($conexion)->selectOne('PRAGMA journal_mode')->journal_mode ?? ''));
            $espera = (int) (DB::connection($conexion)->selectOne('PRAGMA busy_timeout')->timeout ?? 0);

            if ($modo === 'wal' && $espera > 0) {
                $this->anotar('OK', $etiqueta, "sqlite en modo WAL, espera {$espera} ms si está ocupada");
            } elseif ($modo === 'memory') {
                $this->anotar('OK', $etiqueta, 'sqlite en memoria');
            } else {
                $this->anotar('AVISO', $etiqueta, "sqlite en modo {$modo} con espera {$espera} ms: con varios usuarios se pueden perder registros. Usá DB_JOURNAL_MODE=WAL y DB_BUSY_TIMEOUT=5000");
            }
        }
    }


    private function herramientas(): void
    {
        try {
            $gs = new Process(['gs', '--version']);
            $gs->setTimeout(5);
            $gs->run();
            $ok = $gs->isSuccessful();
            $version = trim($gs->getOutput());
        } catch (Throwable) {
            $ok = false;
            $version = '';
        }

        $this->anotar($ok ? 'OK' : 'AVISO', 'Ghostscript', $ok ? "versión {$version}" : 'no está instalado: los PDF se guardan sin achicar (sudo apt install ghostscript)');
        $this->anotar(extension_loaded('gd') ? 'OK' : 'ERROR', 'Extensión GD', extension_loaded('gd') ? 'disponible (achica las fotos JPEG)' : 'falta php-gd');

        if (config('sistema.antivirus.activo')) {
            $this->anotar('OK', 'Antivirus', 'activado: ' . config('sistema.antivirus.comando'));
        } else {
            $this->anotar('AVISO', 'Antivirus', 'desactivado (ANTIVIRUS_ACTIVO=false): los archivos subidos no se revisan con un antivirus');
        }
    }


    private function respaldos(): void
    {
        $this->anotar(config('sistema.backup.password') ? 'OK' : 'AVISO', 'Clave de los backups', config('sistema.backup.password') ? 'definida (BACKUP_ARCHIVE_PASSWORD)' : 'falta BACKUP_ARCHIVE_PASSWORD: el zip no va cifrado ni incluye el .env');

        $discos = count((array) config('sistema.backup.discos', []));
        $this->anotar($discos > 1 ? 'OK' : 'AVISO', 'Copias fuera del servidor', $discos > 1 ? "{$discos} destinos" : 'hay un solo destino: si se rompe el servidor se pierden también las copias');

        $zips = glob(storage_path('app/backups/*.zip')) ?: [];
        $ultimo = $zips ? max(array_map('filemtime', $zips)) : null;

        if (! $ultimo) {
            $this->anotar('AVISO', 'Último backup', 'todavía no hay ninguno: php artisan sistema:backup');
        } else {
            $horas = (int) round((time() - $ultimo) / 3600);
            $this->anotar($horas <= 48 ? 'OK' : 'AVISO', 'Último backup', "hace {$horas} hora(s)");
        }
    }


    private function programador(): void
    {
        $latido = Cache::get('sistema.scheduler_latido');

        if ($latido && (time() - (int) $latido) < 180) {
            $this->anotar('OK', 'Cron de Laravel', 'está corriendo (el latido es de hace menos de 3 minutos)');
        } else {
            $this->anotar('AVISO', 'Cron de Laravel', 'no se detecta: sin él no se hacen los backups ni las verificaciones. Agregá: * * * * * cd ' . base_path() . ' && php artisan schedule:run >> /dev/null 2>&1');
        }
    }


    private function cuentas(): void
    {
        $de_prueba = User::where('email', 'like', '%@prueba.tribunal.test')->count();

        if ($de_prueba > 0) {
            $this->anotar(app()->environment('production') ? 'ERROR' : 'AVISO', 'Usuarios de prueba', "hay {$de_prueba} usuario(s) @prueba.tribunal.test: borrarlos antes de usar el sistema de verdad (./2_borrar_todo.sh)");
        } else {
            $this->anotar('OK', 'Usuarios de prueba', 'no hay');
        }

        // las claves que figuraban en los datos viejos
        $conocida = false;
        foreach (User::whereIn('rol', ['administrador', 'rrhh', 'funcionario'])->limit(60)->get(['password']) as $u) {
            foreach (['Legajo2026!', '123456', 'password', 'Admin123!'] as $clave) {
                if (Hash::check($clave, $u->password)) {
                    $conocida = true;
                    break 2;
                }
            }
        }

        $this->anotar($conocida ? 'ERROR' : 'OK', 'Claves conocidas', $conocida ? 'algún administrador, RRHH o funcionario tiene una clave de las de ejemplo: cambiarla' : 'ninguna de las claves de ejemplo funciona');
    }
}
