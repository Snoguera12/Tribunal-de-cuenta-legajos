<?php

namespace App\Models;

use App\Services\DatosDelCliente;
use App\Services\DetectorIncidentes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


class Auditoria extends Model
{

    // esta en la base aparte
    protected $connection = 'historico';

    // solo created_at, un registro de auditoria no se modifica
    const UPDATED_AT = null;

    protected $table = 'auditorias';

    protected $guarded = [];

    // en false no guarda nada
    public static bool $activo = true;

    public const SECRETOS = ['password', 'remember_token', 'token'];


    protected function casts(): array
    {
        return [
            'valores_anteriores' => 'array',
            'valores_nuevos' => 'array',
            'extra' => 'array',
            'created_at' => 'datetime',
        ];
    }


    protected static function booted(): void
    {
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }


    // evento => [nombre, categoria, severidad]
    public static function catalogo(): array
    {
        return [
            'creado' => ['Creado', 'datos', 'info'],
            'modificado' => ['Modificado', 'datos', 'info'],
            'eliminado' => ['Eliminado', 'datos', 'aviso'],
            'eliminado_definitivo' => ['Eliminado definitivo', 'datos', 'alta'],
            'restaurado' => ['Restaurado', 'datos', 'info'],
            'cambio_rol' => ['Cambio de rol', 'seguridad', 'alta'],
            'cambio_password' => ['Cambio de contraseña', 'seguridad', 'aviso'],
            'cambio_email' => ['Cambio de correo', 'seguridad', 'aviso'],
            'usuario_deshabilitado' => ['Usuario deshabilitado', 'seguridad', 'aviso'],
            'usuario_habilitado' => ['Usuario habilitado', 'seguridad', 'info'],
            'usuario_no_deshabilitado' => ['Usuario no deshabilitado', 'seguridad', 'alta'],
            'login_advertencia' => ['Último intento de clave', 'acceso', 'aviso'],
            'cuenta_bloqueada_temporal' => ['Usuario bloqueado (temporal)', 'seguridad', 'alta'],
            'cuenta_bloqueada_permanente' => ['Usuario bloqueado (definitivo)', 'seguridad', 'critica'],
            'cuenta_desbloqueada' => ['Usuario desbloqueado', 'seguridad', 'aviso'],
            'intentos_restaurados' => ['Intentos de clave devueltos', 'acceso', 'info'],
            'primer_ingreso' => ['Primer ingreso', 'acceso', 'info'],
            'sesion_desplazada' => ['Sesión cerrada por otro ingreso', 'acceso', 'aviso'],
            'sesion_duplicada_bloqueada' => ['Segundo ingreso bloqueado', 'acceso', 'aviso'],
            'sesion_cerrada_por_admin' => ['Sesión cerrada por el administrador', 'acceso', 'aviso'],

            'login' => ['Inicio de sesión', 'acceso', 'info'],
            'logout' => ['Cierre de sesión', 'acceso', 'info'],
            'login_fallido' => ['Inicio de sesión fallido', 'acceso', 'aviso'],
            'sesion_nueva_ip' => ['Ingreso desde una IP nueva', 'acceso', 'aviso'],
            'password_reset' => ['Contraseña restablecida', 'seguridad', 'aviso'],
            'navegacion' => ['Navegación', 'acceso', 'info'],
            'acceso_denegado' => ['Acceso denegado', 'seguridad', 'alta'],

            'ver_persona' => ['Vio un perfil', 'datos', 'info'],
            'ver_documento' => ['Abrió un documento', 'documento', 'info'],
            'archivo_subido' => ['Archivo subido', 'documento', 'info'],
            'archivo_rechazado' => ['Archivo rechazado', 'seguridad', 'alta'],
            'archivo_ilegible' => ['Archivo ilegible', 'documento', 'alta'],

            'baja' => ['Baja de legajo', 'datos', 'aviso'],
            'alta' => ['Alta de legajo', 'datos', 'info'],

            'api_consulta' => ['Consulta API', 'api', 'info'],
            'api_denegado' => ['API denegada', 'api', 'alta'],
            'token_generado' => ['Token generado', 'seguridad', 'aviso'],
            'token_eliminado' => ['Token eliminado', 'seguridad', 'aviso'],

            'exportacion' => ['Exportación', 'datos', 'aviso'],
            'importacion' => ['Importación', 'datos', 'aviso'],

            'backup_ok' => ['Backup realizado', 'backup', 'info'],
            'backup_fallo' => ['Backup fallido', 'backup', 'critica'],
            'backup_verificado' => ['Backup verificado', 'backup', 'info'],
            'backup_descargado' => ['Backup descargado', 'backup', 'aviso'],
            'backup_restaurado' => ['Backup restaurado', 'backup', 'alta'],
            'backup_viejo' => ['Backup desactualizado', 'backup', 'alta'],

            'error_sistema' => ['Error del sistema', 'sistema', 'alta'],
            'historico_error' => ['Error al guardar histórico', 'sistema', 'critica'],
            'auditoria_alterada' => ['Auditoría alterada', 'seguridad', 'critica'],
            'auditoria_archivada' => ['Auditoría archivada', 'sistema', 'aviso'],
            'incidente_abierto' => ['Incidente abierto', 'incidente', 'alta'],
            'incidente_cerrado' => ['Incidente cerrado', 'incidente', 'info'],
        ];
    }


    public static function eventos(): array
    {
        return collect(self::catalogo())->map(fn ($datos) => $datos[0])->all();
    }


    public static function severidades(): array
    {
        return ['info' => 'Info', 'aviso' => 'Aviso', 'alta' => 'Alta', 'critica' => 'Crítica'];
    }


    public static function categorias(): array
    {
        return [
            'acceso' => 'Acceso', 'datos' => 'Datos', 'documento' => 'Documentos',
            'seguridad' => 'Seguridad', 'api' => 'API', 'backup' => 'Backup',
            'sistema' => 'Sistema', 'incidente' => 'Incidentes',
        ];
    }


    // guarda un registro. Si falla no rompe la app, solo escribe en el log de laravel
    public static function registrar(
        string $evento,
        string $descripcion,
        ?Model $modelo = null,
        ?array $antes = null,
        ?array $despues = null,
        ?array $extra = null,
        $actor = null,
        ?string $severidad = null,
    ): ?self {

        if (! self::$activo) {
            return null;
        }

        try {

            $meta = self::catalogo()[$evento] ?? [$evento, 'sistema', 'info'];

            $datos = [
                ...self::datosDelActor($actor),
                'evento' => $evento,
                'categoria' => $meta[1],
                'severidad' => $severidad ?? $meta[2],
                'descripcion' => $descripcion,
                'modelo' => $modelo ? class_basename($modelo) : null,
                'modelo_id' => $modelo?->getKey(),
                'valores_anteriores' => self::limpiar($antes),
                'valores_nuevos' => self::limpiar($despues),
                'extra' => self::limpiar($extra),
                ...self::datosDeLaPeticion(),
            ];

            $registro = self::guardarEncadenado($datos);

            DetectorIncidentes::evaluar($registro);

            return $registro;

        } catch (\Throwable $e) {

            Log::error('No se pudo guardar la auditoría: ' . $e->getMessage());

            return null;
        }
    }


    // ---------------------------------------------------------- cadena de hashes

    private const CAMPOS_HASH = [
        'actor_tipo', 'actor_id', 'actor_nombre', 'actor_rol', 'evento', 'categoria', 'severidad',
        'descripcion', 'modelo', 'modelo_id', 'valores_anteriores', 'valores_nuevos', 'extra',
        'ip', 'mac', 'navegador', 'sistema_operativo', 'dispositivo', 'url', 'metodo', 'sesion',
        'peticion', 'created_at',
    ];


    // guarda con el hash del anterior (se bloquea el ultimo para que no se mezclen)
    private static function guardarEncadenado(array $datos): self
    {
        return DB::connection('historico')->transaction(function () use ($datos) {

            $ultimo = self::query()->whereNotNull('hash')->orderByDesc('id')->lockForUpdate()->first(['id', 'hash']);

            $datos['created_at'] = now()->format('Y-m-d H:i:s');
            $datos['hash_previo'] = $ultimo?->hash ?? str_repeat('0', 64);
            $datos['hash'] = self::calcularHash($datos, $datos['hash_previo']);

            return self::create($datos);
        });
    }


    // hmac con una clave sacada de la APP_KEY: sin ella no se puede rehacer la cadena
    public static function calcularHash(array $datos, string $previo): string
    {
        $partes = [$previo];

        foreach (self::CAMPOS_HASH as $campo) {

            $valor = $datos[$campo] ?? null;

            if (is_array($valor)) {
                $valor = json_encode(self::ordenar($valor), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } elseif ($valor instanceof \DateTimeInterface) {
                $valor = $valor->format('Y-m-d H:i:s');
            }

            $partes[] = $valor === null ? '' : (string) $valor;
        }

        $clave = hash('sha256', 'auditoria|' . config('app.key'));

        return hash_hmac('sha256', implode("\x1F", $partes), $clave);
    }


    private static function ordenar(array $valores): array
    {
        ksort($valores);

        foreach ($valores as $k => $v) {
            if (is_array($v)) {
                $valores[$k] = self::ordenar($v);
            }
        }

        return $valores;
    }


    // revisa toda la cadena y devuelve los problemas. Los registros anteriores a la cadena (sin hash) se saltean
    public static function verificarCadena(): array
    {
        $problemas = [];
        $previo = null;
        $revisados = 0;
        $sinHash = 0;
        $ultimo = null;

        foreach (self::query()->orderBy('id')->cursor() as $fila) {

            if ($fila->hash === null) {
                $sinHash++;
                continue;
            }

            // el primero de la cadena sigue a ceros o al ultimo registro que se archivo
            if ($previo === null) {
                $previo = $fila->hash_previo;

                if ($previo !== str_repeat('0', 64) && ! AuditoriaArchivo::where('ultimo_hash', $previo)->exists()) {
                    $problemas[] = "Registro #{$fila->id}: faltan registros anteriores y no hay archivo que los respalde";
                }
            } elseif ($fila->hash_previo !== $previo) {
                $problemas[] = "Registro #{$fila->id}: no sigue al anterior (se borró o se insertó algo antes)";
            }

            if (! hash_equals(self::calcularHash(self::atributosParaHash($fila), $fila->hash_previo), $fila->hash)) {
                $problemas[] = "Registro #{$fila->id}: su contenido fue modificado";
            }

            $previo = $fila->hash;
            $ultimo = ['id' => $fila->id, 'hash' => $fila->hash];
            $revisados++;
        }

        // los archivos de auditoria que se guardaron tienen que seguir ahi y sin cambios
        foreach (AuditoriaArchivo::all() as $archivo) {
            $problema = self::revisarArchivo($archivo);

            if ($problema) {
                $problemas[] = $problema;
            }
        }

        // el ultimo registro conocido (guardado fuera de la base) tiene que seguir igual
        $ancla = self::leerAncla();

        if ($ancla) {
            $actual = self::query()->find($ancla['id']);

            if (! $actual || $actual->hash !== $ancla['hash']) {
                $problemas[] = "El registro #{$ancla['id']}, que ya estaba verificado, falta o cambió";
            }
        }

        if (empty($problemas) && $ultimo) {
            self::guardarAncla($ultimo);
        }

        return ['ok' => empty($problemas), 'revisados' => $revisados, 'sin_hash' => $sinHash, 'problemas' => $problemas, 'ultimo' => $ultimo];
    }


    private static function revisarArchivo(AuditoriaArchivo $archivo): ?string
    {
        foreach (config('sistema.backup.discos') ?: ['backups'] as $disco) {

            try {
                $disk = \Illuminate\Support\Facades\Storage::disk($disco);

                if (! $disk->exists($archivo->archivo)) {
                    continue;
                }

                $ctx = hash_init('sha256');
                hash_update_stream($ctx, $disk->readStream($archivo->archivo));

                if (hash_equals($archivo->sha256, hash_final($ctx))) {
                    return null;
                }

                return "El archivo {$archivo->archivo} cambió desde que se guardó (disco {$disco})";

            } catch (\Throwable $e) {
                continue;
            }
        }

        return "No se encuentra el archivo de auditoría {$archivo->archivo}";
    }


    private static function atributosParaHash(self $fila): array
    {
        $datos = [];

        foreach (self::CAMPOS_HASH as $campo) {
            $datos[$campo] = $campo === 'created_at'
                ? $fila->created_at?->format('Y-m-d H:i:s')
                : $fila->{$campo};
        }

        return $datos;
    }


    public static function rutaAncla(): string
    {
        return storage_path('app/auditoria_ancla.json');
    }


    public static function leerAncla(): ?array
    {
        return is_file(self::rutaAncla()) ? json_decode(file_get_contents(self::rutaAncla()), true) : null;
    }


    public static function guardarAncla(array $ultimo): void
    {
        file_put_contents(self::rutaAncla(), json_encode($ultimo + ['verificado' => now()->toIso8601String()]));
    }


    public static function datosDelActor($actor = null): array
    {
        // en el evento Login todavia no esta auth()->user(), por eso se puede pasar a mano
        $actor = $actor ?? auth()->user();

        if ($actor instanceof User) {
            return [
                'actor_tipo' => 'usuario',
                'actor_id' => $actor->id,
                'actor_nombre' => $actor->name,
                'actor_rol' => $actor->rol,
            ];
        }

        if ($actor instanceof ApiClient) {
            return [
                'actor_tipo' => 'api',
                'actor_id' => $actor->id,
                'actor_nombre' => $actor->nombre,
                'actor_rol' => null,
            ];
        }

        return [
            'actor_tipo' => app()->runningInConsole() ? 'sistema' : 'anonimo',
            'actor_id' => null,
            'actor_nombre' => null,
            'actor_rol' => null,
        ];
    }


    public static function datosDeLaPeticion(): array
    {
        // en consola no hay peticion (en los tests si se usa)
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return [];
        }

        $peticion = request();
        $url = $peticion->fullUrl();

        // las acciones de livewire llegan a /livewire/update, el referer tiene la pagina real
        if (str_contains($url, '/livewire') && $peticion->headers->get('referer')) {
            $url = $peticion->headers->get('referer');
        }

        $ip = $peticion->ip();
        $ua = DatosDelCliente::navegador($peticion->userAgent());

        // un id por peticion para poder agrupar todo lo que paso en la misma
        if (! $peticion->attributes->has('peticion_id')) {
            $peticion->attributes->set('peticion_id', (string) Str::uuid());
        }

        $sesion = null;
        if ($peticion->hasSession() && $peticion->session()->getId()) {
            $sesion = substr(hash('sha256', $peticion->session()->getId()), 0, 16);
        }

        return [
            'ip' => $ip,
            'mac' => DatosDelCliente::mac($ip),
            'navegador' => $peticion->userAgent(),
            'sistema_operativo' => $ua['so'] . ' / ' . $ua['navegador'],
            'dispositivo' => $ua['dispositivo'],
            'url' => $url,
            'metodo' => $peticion->method(),
            'sesion' => $sesion,
            'peticion' => $peticion->attributes->get('peticion_id'),
        ];
    }


    // oculta secretos y deja todo como texto o numero para guardarlo en json
    public static function limpiar(?array $valores): ?array
    {
        if ($valores === null) {
            return null;
        }

        $salida = [];

        foreach ($valores as $campo => $valor) {

            if (in_array($campo, self::SECRETOS, true)) {
                $salida[$campo] = '[oculto]';
                continue;
            }

            if ($valor instanceof \BackedEnum) {
                $valor = $valor->value;
            } elseif ($valor instanceof \DateTimeInterface) {
                $valor = $valor->format('Y-m-d H:i:s');
            } elseif (is_array($valor) || is_object($valor)) {
                $valor = json_encode($valor, JSON_UNESCAPED_UNICODE);
            }

            if (is_string($valor) && mb_strlen($valor) > 500) {
                $valor = mb_substr($valor, 0, 500) . '...';
            }

            $salida[$campo] = $valor;
        }

        return $salida;
    }
}
