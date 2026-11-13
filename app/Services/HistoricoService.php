<?php

namespace App\Services;

use App\Models\Auditoria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;


// copia completa de cada fila ANTES de modificarla, en la tabla h_<tabla> (solo se agrega, nunca se borra ni se cambia)
class HistoricoService
{
    // columnas propias del historial
    private const META = ['hist_id', 'hist_operacion', 'hist_version', 'hist_fecha', 'hist_user_id', 'hist_user_nombre', 'hist_ip', 'hist_peticion'];
    
    // conexión de la base separada donde vive el historial (ver config/database.php)
    private const CONEXION = 'historico';

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


    // crea la tabla historica o le agrega las columnas que le falten
    public static function sincronizarTabla(string $tabla): string
    {
        $origen = Schema::getColumns($tabla);
        $historica = self::nombre($tabla);

        if (! Schema::connection(self::CONEXION)->hasTable($historica)) {

            Schema::connection(self::CONEXION)->create($historica, function (Blueprint $t) use ($origen) {

                $t->id('hist_id');
                $t->string('hist_operacion', 15);
                $t->unsignedInteger('hist_version')->default(1);
                $t->timestamp('hist_fecha')->useCurrent();
                $t->unsignedBigInteger('hist_user_id')->nullable();
                $t->string('hist_user_nombre')->nullable();
                $t->string('hist_ip', 45)->nullable();
                $t->string('hist_peticion', 36)->nullable();

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

        $existentes = Schema::connection(self::CONEXION)->getColumnListing($historica);
        $faltan = collect($origen)->filter(fn ($c) => ! in_array($c['name'], $existentes, true)
            && ! in_array($c['name'], self::IGNORAR, true)
            && ! in_array($c['name'], self::META, true));

        if ($faltan->isEmpty()) {
            return 'al día';
        }

        Schema::connection(self::CONEXION)->table($historica, function (Blueprint $t) use ($faltan) {
            foreach ($faltan as $columna) {
                self::agregarColumna($t, $columna);
            }
        });

        self::$columnasCache = [];

        return 'columnas agregadas: ' . $faltan->pluck('name')->implode(', ');
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


    private static function columnas(string $historica): array
    {
        return self::$columnasCache[$historica] ??= Schema::connection(self::CONEXION)->getColumnListing($historica);
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


    // guarda la fila tal como estaba. operacion: UPDATE o BASE. Si falla no corta el guardado: lo deja en la auditoria
    public static function guardar(Model $modelo, string $operacion, array $fila): void
    {
        if (! Auditoria::$activo) {
            return;
        }

        try {

            $tabla = $modelo->getTable();
            $historica = self::nombre($tabla);

            if (! Schema::connection(self::CONEXION)->hasTable($historica)) {
                self::sincronizarTabla($tabla);
            }

            $datos = self::limpiar($fila, self::columnas($historica));

            $actor = Auditoria::datosDelActor();
            $peticion = Auditoria::datosDeLaPeticion();

            $version = DB::connection(self::CONEXION)->table($historica)->where('id', $fila['id'] ?? null)->max('hist_version');

            Auditoria::conReintentos(fn () => DB::connection(self::CONEXION)->table($historica)->insert([
                ...$datos,
                'hist_operacion' => $operacion,
                'hist_version' => ((int) $version) + 1,
                'hist_fecha' => now(),
                'hist_user_id' => $actor['actor_tipo'] === 'usuario' ? $actor['actor_id'] : null,
                'hist_user_nombre' => $actor['actor_nombre'],
                'hist_ip' => $peticion['ip'] ?? null,
                'hist_peticion' => $peticion['peticion'] ?? null,
            ]));

        } catch (\Throwable $e) {

            Log::error('No se pudo guardar el historial de ' . $modelo->getTable() . ': ' . $e->getMessage());

            Auditoria::registrar(
                evento: 'historico_error',
                descripcion: 'No se pudo guardar el historial de ' . $modelo->getTable() . ' #' . $modelo->getKey(),
                extra: ['error' => $e->getMessage()],
            );
        }
    }


    // todas las versiones guardadas de un registro (la mas nueva primero)
    public static function versiones(string $tabla, int|string $id)
    {
        $historica = self::nombre($tabla);

        if (! Schema::connection(self::CONEXION)->hasTable($historica)) {
            return collect();
        }

        return DB::connection(self::CONEXION)->table($historica)->where('id', $id)->orderByDesc('hist_version')->get();
    }

    // dice si la tabla del historial ya existe
    public static function existe(string $tabla): bool
    {
        return Schema::connection(self::CONEXION)->hasTable(self::nombre($tabla));
    }


    // los últimos cambios guardados de una tabla (el más nuevo primero)
    public static function ultimos(string $tabla, int $limite = 50)
    {
        return DB::connection(self::CONEXION)->table(self::nombre($tabla))->orderByDesc('hist_id')->limit($limite)->get();
    }
    
    // copia lo que hay ahora como version base (para no perder lo ya cargado)
    public static function copiarActual(string $tabla): int
    {
        self::sincronizarTabla($tabla);

        $historica = self::nombre($tabla);
        $columnas = self::columnas($historica);
        $total = 0;

        DB::table($tabla)->orderBy('id')->chunk(200, function ($filas) use ($historica, $columnas, &$total) {

            foreach ($filas as $fila) {

                $fila = (array) $fila;

                if (DB::connection(self::CONEXION)->table($historica)->where('id', $fila['id'])->where('hist_operacion', 'BASE')->exists()) {
                    continue;
                }

                DB::connection(self::CONEXION)->table($historica)->insert([
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
