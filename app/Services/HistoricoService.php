<?php

namespace App\Services;

use App\Models\Auditoria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;


// copia completa de cada fila ANTES de modificarla, en la tabla h_<tabla> (solo se agrega, nunca se borra ni se cambia).
// Se guarda SIEMPRE en la base principal y, además, se copia al otro servidor (conexión "historico").
// Si el otro servidor no responde, el sistema sigue funcionando: lo que no llegó queda marcado como pendiente
// (hist_sincronizado_en vacío) y se manda después con "php artisan historico:enviar".
class HistoricoService
{
    // columnas propias del historial
    private const META = ['hist_id', 'hist_operacion', 'hist_version', 'hist_fecha', 'hist_user_id', 'hist_user_nombre', 'hist_ip', 'hist_peticion', 'hist_sincronizado_en'];

    // conexión del OTRO servidor, donde vive la copia del historial (ver config/database.php)
    private const ESPEJO = 'historico';

    // mientras exista esta marca no se intenta escribir en el otro servidor (evita demoras si está apagado)
    private const CLAVE_CAIDO = 'historico_otro_servidor_caido';

    // nunca se copian (el hash de la clave no tiene por que estar duplicado en otra tabla)
    private const IGNORAR = ['remember_token', 'password'];

    // cambios que no son una modificacion real: si solo cambiaron estas columnas no se guarda version
    private const RUIDO = ['updated_at', 'remember_token', 'sesion_activa_id', 'sesion_activa_ip', 'sesion_activa_desde',
        'ultima_actividad_en', 'primer_ingreso_en'];

    private static array $columnasCache = [];


    public static function nombre(string $tabla): string
    {
        return 'h_' . $tabla;
    }


    // [tabla => clase del modelo] de las tablas con historial
    public static function modelos(): array
    {
        $tablas = [];

        foreach (config('sistema.tablas_historico', []) as $clase) {
            $tablas[(new $clase)->getTable()] = $clase;
        }

        return $tablas;
    }


    public static function clasePara(string $tabla): ?string
    {
        return self::modelos()[$tabla] ?? null;
    }


    // dice si entre estos cambios hay alguno que valga la pena guardar
    public static function cambioReal(array $cambios): bool
    {
        return count(array_diff(array_keys($cambios), self::RUIDO)) > 0;
    }


    // crea la tabla historica (en la principal y en el otro servidor) o le agrega las columnas que le falten.
    // Si el otro servidor no responde lo avisa en el texto y sigue
    public static function sincronizarTabla(string $tabla): string
    {
        $estado = 'principal: ' . self::sincronizarEn(null, $tabla);

        try {
            $estado .= ' · otro servidor: ' . self::sincronizarEn(self::ESPEJO, $tabla);
        } catch (\Throwable $e) {
            $estado .= ' · otro servidor: NO DISPONIBLE (' . mb_strimwidth($e->getMessage(), 0, 120, '…') . ')';
        }

        return $estado;
    }


    // $conexion null = base principal. Solo la principal lleva la columna hist_sincronizado_en
    private static function sincronizarEn(?string $conexion, string $tabla): string
    {
        $esquema = Schema::connection($conexion);
        $esPrincipal = $conexion === null;
        $origen = Schema::getColumns($tabla);
        $historica = self::nombre($tabla);

        if (! $esquema->hasTable($historica)) {

            $esquema->create($historica, function (Blueprint $t) use ($origen, $esPrincipal) {

                $t->id('hist_id');
                $t->string('hist_operacion', 15);
                $t->unsignedInteger('hist_version')->default(1);
                $t->timestamp('hist_fecha')->useCurrent();
                $t->unsignedBigInteger('hist_user_id')->nullable();
                $t->string('hist_user_nombre')->nullable();
                $t->string('hist_ip', 45)->nullable();
                $t->string('hist_peticion', 36)->nullable();

                if ($esPrincipal) {
                    // vacío = todavía no llegó al otro servidor
                    $t->timestamp('hist_sincronizado_en')->nullable()->index();
                }

                foreach ($origen as $columna) {
                    if (in_array($columna['name'], self::IGNORAR, true) || in_array($columna['name'], self::META, true)) {
                        continue;
                    }

                    self::agregarColumna($t, $columna);
                }

                $t->index('id');
            });

            self::$columnasCache = [];

            return 'creada';
        }

        $existentes = $esquema->getColumnListing($historica);
        $faltan = collect($origen)->filter(fn ($c) => ! in_array($c['name'], $existentes, true)
            && ! in_array($c['name'], self::IGNORAR, true)
            && ! in_array($c['name'], self::META, true));
        $faltaMarca = $esPrincipal && ! in_array('hist_sincronizado_en', $existentes, true);

        if ($faltan->isEmpty() && ! $faltaMarca) {
            return 'al día';
        }

        $esquema->table($historica, function (Blueprint $t) use ($faltan, $faltaMarca) {

            if ($faltaMarca) {
                $t->timestamp('hist_sincronizado_en')->nullable()->index();
            }

            foreach ($faltan as $columna) {
                self::agregarColumna($t, $columna);
            }
        });

        self::$columnasCache = [];

        $agregadas = $faltan->pluck('name')->when($faltaMarca, fn ($c) => $c->prepend('hist_sincronizado_en'));

        return 'columnas agregadas: ' . $agregadas->implode(', ');
    }


    // todas las columnas son nullable y sin restricciones: es solo una copia
    private static function agregarColumna(Blueprint $t, array $columna): void
    {
        $nombre = $columna['name'];
        $tipo = strtolower($columna['type_name'] . ' ' . ($columna['type'] ?? ''));

        $definicion = match (true) {
            str_contains($tipo, 'tinyint(1)'), str_contains($tipo, 'bool') => $t->boolean($nombre),
            str_contains($tipo, 'int') => $t->bigInteger($nombre),
            str_contains($tipo, 'datetime'), str_contains($tipo, 'timestamp') => $t->dateTime($nombre),
            str_contains($tipo, 'date') => $t->date($nombre),
            str_contains($tipo, 'float'), str_contains($tipo, 'double'), str_contains($tipo, 'decimal') => $t->double($nombre),
            default => $t->text($nombre),
        };

        $definicion->nullable();
    }


    // $conexion null = base principal
    private static function columnas(string $historica, ?string $conexion = null): array
    {
        return self::$columnasCache[($conexion ?? 'principal') . '.' . $historica]
            ??= Schema::connection($conexion)->getColumnListing($historica);
    }


    private static function limpiar(array $fila, array $columnas): array
    {
        $datos = [];

        foreach ($fila as $campo => $valor) {

            if (! in_array($campo, $columnas, true) || in_array($campo, self::META, true) || in_array($campo, self::IGNORAR, true)) {
                continue;
            }

            if ($valor instanceof \BackedEnum) {
                $valor = $valor->value;
            } elseif ($valor instanceof \DateTimeInterface) {
                $valor = $valor->format('Y-m-d H:i:s');
            } elseif (is_array($valor) || is_object($valor)) {
                $valor = json_encode($valor, JSON_UNESCAPED_UNICODE);
            }

            $datos[$campo] = $valor;
        }

        return $datos;
    }


    // guarda la fila tal como estaba. operacion: UPDATE o BASE.
    // 1) se guarda en la principal (si esto falla no corta el guardado: queda en la auditoria)
    // 2) se copia al otro servidor (si falla, queda pendiente y no se nota)
    public static function guardar(Model $modelo, string $operacion, array $fila): void
    {
        if (! Auditoria::$activo) {
            return;
        }

        $histId = null;

        try {

            $tabla = $modelo->getTable();
            $historica = self::nombre($tabla);

            if (! Schema::hasTable($historica)) {
                self::sincronizarEn(null, $tabla);
            }

            $datos = self::limpiar($fila, self::columnas($historica));

            $actor = Auditoria::datosDelActor();
            $peticion = Auditoria::datosDeLaPeticion();

            $version = DB::table($historica)->where('id', $fila['id'] ?? null)->max('hist_version');

            Auditoria::conReintentos(function () use ($historica, $datos, $operacion, $version, $actor, $peticion, &$histId) {
                $histId = DB::table($historica)->insertGetId([
                    ...$datos,
                    'hist_operacion' => $operacion,
                    'hist_version' => ((int) $version) + 1,
                    'hist_fecha' => now(),
                    'hist_user_id' => $actor['actor_tipo'] === 'usuario' ? $actor['actor_id'] : null,
                    'hist_user_nombre' => $actor['actor_nombre'],
                    'hist_ip' => $peticion['ip'] ?? null,
                    'hist_peticion' => $peticion['peticion'] ?? null,
                ], 'hist_id');
            });

        } catch (\Throwable $e) {

            Log::error('No se pudo guardar el historial de ' . $modelo->getTable() . ': ' . $e->getMessage());

            Auditoria::registrar(
                evento: 'historico_error',
                descripcion: 'No se pudo guardar el historial de ' . $modelo->getTable() . ' #' . $modelo->getKey(),
                extra: ['error' => $e->getMessage()],
            );

            return;
        }

        if ($histId) {
            self::enviarFila($historica, (int) $histId);
        }
    }


    // copia una versión recién guardada al otro servidor. NUNCA lanza error: si no puede, queda pendiente
    private static function enviarFila(string $historica, int $histId): void
    {
        if (Cache::has(self::CLAVE_CAIDO)) {
            return;
        }

        try {

            $fila = DB::table($historica)->where('hist_id', $histId)->first();

            if (! $fila) {
                return;
            }

            self::copiarAlOtroServidor($historica, [(array) $fila]);

            DB::table($historica)->where('hist_id', $histId)->update(['hist_sincronizado_en' => now()]);

        } catch (\Throwable $e) {

            // un minuto sin insistir, para no demorar cada guardado mientras el otro servidor está apagado
            Cache::put(self::CLAVE_CAIDO, true, now()->addMinute());

            Log::warning('El otro servidor del historial no responde, queda pendiente: ' . $e->getMessage());
        }
    }


    // inserta filas (con el mismo hist_id) en el otro servidor. Si ya estaban, no las repite. Lanza error si no responde
    private static function copiarAlOtroServidor(string $historica, array $filas): void
    {
        if (empty($filas)) {
            return;
        }

        $tabla = substr($historica, 2);

        if (! Schema::connection(self::ESPEJO)->hasTable($historica)) {
            self::sincronizarEn(self::ESPEJO, $tabla);
        }

        $columnas = self::columnas($historica, self::ESPEJO);
        $faltan = array_diff(array_keys($filas[0]), $columnas, ['hist_sincronizado_en']);

        // la tabla del otro servidor quedó atrasada (se agregó una columna): se actualiza
        if (! empty($faltan)) {
            self::sincronizarEn(self::ESPEJO, $tabla);
            $columnas = self::columnas($historica, self::ESPEJO);
        }

        $lote = array_map(fn ($fila) => array_intersect_key($fila, array_flip($columnas)), $filas);

        DB::connection(self::ESPEJO)->table($historica)->insertOrIgnore($lote);
    }


    // manda al otro servidor todo lo que todavía no llegó. Devuelve [enviadas, quedan pendientes].
    // Si el otro servidor no responde lanza el error (lo atrapa el comando)
    public static function enviarPendientes(string $tabla): array
    {
        $historica = self::nombre($tabla);

        if (! Schema::hasTable($historica) || ! in_array('hist_sincronizado_en', self::columnas($historica), true)) {
            return [0, 0];
        }

        $enviadas = 0;

        DB::table($historica)->whereNull('hist_sincronizado_en')->chunkById(100, function ($filas) use ($historica, &$enviadas) {

            $filas = $filas->map(fn ($f) => (array) $f)->all();

            self::copiarAlOtroServidor($historica, $filas);

            DB::table($historica)->whereIn('hist_id', array_column($filas, 'hist_id'))->update(['hist_sincronizado_en' => now()]);

            $enviadas += count($filas);

        }, 'hist_id');

        return [$enviadas, DB::table($historica)->whereNull('hist_sincronizado_en')->count()];
    }


    // marca todo el historial como "pendiente" (para volver a mandarlo entero si el otro servidor perdió los datos)
    public static function marcarTodoPendiente(string $tabla): void
    {
        $historica = self::nombre($tabla);

        if (Schema::hasTable($historica) && in_array('hist_sincronizado_en', self::columnas($historica), true)) {
            DB::table($historica)->update(['hist_sincronizado_en' => null]);
        }
    }


    // vuelve a intentar con el otro servidor aunque haya fallado hace poco
    public static function olvidarCaida(): void
    {
        Cache::forget(self::CLAVE_CAIDO);
    }


    // todas las versiones guardadas de un registro (la mas nueva primero). Se lee de la principal
    public static function versiones(string $tabla, int|string $id)
    {
        $historica = self::nombre($tabla);

        if (! Schema::hasTable($historica)) {
            return collect();
        }

        return DB::table($historica)->where('id', $id)->orderByDesc('hist_version')->get();
    }

    // dice si la tabla del historial ya existe
    public static function existe(string $tabla): bool
    {
        return Schema::hasTable(self::nombre($tabla));
    }


    // los últimos cambios guardados de una tabla (el más nuevo primero)
    public static function ultimos(string $tabla, int $limite = 50)
    {
        return DB::table(self::nombre($tabla))->orderByDesc('hist_id')->limit($limite)->get();
    }

    // copia lo que hay ahora como version base (para no perder lo ya cargado). Queda pendiente de mandar al otro servidor
    public static function copiarActual(string $tabla): int
    {
        self::sincronizarEn(null, $tabla);

        $historica = self::nombre($tabla);
        $columnas = self::columnas($historica);
        $total = 0;

        DB::table($tabla)->orderBy('id')->chunk(200, function ($filas) use ($historica, $columnas, &$total) {

            foreach ($filas as $fila) {

                $fila = (array) $fila;

                if (DB::table($historica)->where('id', $fila['id'])->where('hist_operacion', 'BASE')->exists()) {
                    continue;
                }

                DB::table($historica)->insert([
                    ...self::limpiar($fila, $columnas),
                    'hist_operacion' => 'BASE',
                    'hist_version' => 0,
                    'hist_fecha' => now(),
                ]);

                $total++;
            }
        });

        return $total;
    }


    // vuelve el registro a lo que tenia la version indicada. Pasa por el modelo: el estado de ahora
    // queda guardado como una version mas y la auditoria anota el cambio. Devuelve un texto de error o null
    public static function restaurar(string $tabla, int|string $id, int $version): ?string
    {
        $clase = self::clasePara($tabla);

        if (! $clase) {
            return 'Esa tabla no tiene historial.';
        }

        $guardada = self::versiones($tabla, $id)->firstWhere('hist_version', $version);

        if (! $guardada) {
            return 'No existe esa versión.';
        }

        $modelo = $clase::withoutGlobalScopes()->find($id);

        if (! $modelo) {
            return 'El registro ya no existe.';
        }

        // no se tocan la clave, el id ni lo que ya no existe en la tabla
        $columnasHoy = Schema::getColumnListing($tabla);
        $datos = collect((array) $guardada)
            ->reject(fn ($v, $campo) => str_starts_with($campo, 'hist_') || in_array($campo, [...self::IGNORAR, 'id', 'created_at'], true))
            ->only($columnasHoy)
            ->all();

        // valores tal como estan en la base: no pasan por los casts
        $modelo->setRawAttributes(array_merge($modelo->getAttributes(), $datos), false);

        if (! $modelo->isDirty()) {
            return 'El registro ya está igual a esa versión.';
        }

        $modelo->save();

        Auditoria::registrar(
            evento: 'modificado',
            descripcion: "Restauró {$tabla} #{$id} a la versión {$version} del historial",
            modelo: $modelo,
            extra: ['tabla' => $tabla, 'version' => $version],
            severidad: 'alta',
        );

        return null;
    }
}
