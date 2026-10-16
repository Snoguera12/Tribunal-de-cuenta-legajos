<?php

namespace App\Services;

use App\Models\Auditoria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;


// copia completa de cada fila (antes de modificarla o borrarla) en la base aparte: tabla h_<tabla>
class HistoricoService
{

    private const CONEXION = 'historico';

    // columnas propias del historico
    private const META = ['hist_id', 'hist_operacion', 'hist_version', 'hist_fecha', 'hist_user_id', 'hist_user_nombre', 'hist_ip', 'hist_peticion'];

    // nunca se copian
    private const IGNORAR = ['remember_token'];

    private static array $columnasCache = [];


    public static function nombre(string $tabla): string
    {
        return 'h_' . $tabla;
    }


    public static function esquema()
    {
        return Schema::connection(self::CONEXION);
    }


    // crea la tabla historica o le agrega las columnas que le falten
    public static function sincronizarTabla(string $tabla): string
    {
        $origen = Schema::getColumns($tabla);
        $historica = self::nombre($tabla);

        if (! self::esquema()->hasTable($historica)) {

            self::esquema()->create($historica, function (Blueprint $t) use ($origen) {

                $t->id('hist_id');
                $t->string('hist_operacion', 15);
                $t->unsignedInteger('hist_version')->default(1);
                $t->timestamp('hist_fecha')->useCurrent();
                $t->unsignedBigInteger('hist_user_id')->nullable();
                $t->string('hist_user_nombre')->nullable();
                $t->string('hist_ip', 45)->nullable();
                $t->string('hist_peticion', 36)->nullable();

                foreach ($origen as $columna) {

                    if (in_array($columna['name'], self::IGNORAR, true)) {
                        continue;
                    }

                    self::agregarColumna($t, $columna);
                }
            });

            self::esquema()->table($historica, function (Blueprint $t) {
                $t->index('id');
            });

            self::$columnasCache = [];

            return 'creada';
        }

        $existentes = self::esquema()->getColumnListing($historica);
        $faltan = collect($origen)->filter(fn ($c) => ! in_array($c['name'], $existentes, true)
            && ! in_array($c['name'], self::IGNORAR, true));

        if ($faltan->isEmpty()) {
            return 'al día';
        }

        self::esquema()->table($historica, function (Blueprint $t) use ($faltan) {
            foreach ($faltan as $columna) {
                self::agregarColumna($t, $columna);
            }
        });

        self::$columnasCache = [];

        return 'columnas agregadas: ' . $faltan->pluck('name')->implode(', ');
    }


    // todas las columnas son nullable y sin restricciones, es solo una copia
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
        return self::$columnasCache[$historica] ??= self::esquema()->getColumnListing($historica);
    }


    // guarda la fila tal como estaba. operacion: UPDATE, DELETE, FORCE_DELETE o BASE
    public static function guardar(Model $modelo, string $operacion, array $fila): void
    {
        try {

            $tabla = $modelo->getTable();
            $historica = self::nombre($tabla);

            if (! self::esquema()->hasTable($historica)) {
                self::sincronizarTabla($tabla);
            }

            $columnas = self::columnas($historica);
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

            $actor = Auditoria::datosDelActor();
            $peticion = Auditoria::datosDeLaPeticion();

            $version = DB::connection(self::CONEXION)->table($historica)
                ->where('id', $fila['id'] ?? null)
                ->max('hist_version');

            DB::connection(self::CONEXION)->table($historica)->insert([
                ...$datos,
                'hist_operacion' => $operacion,
                'hist_version' => ((int) $version) + 1,
                'hist_fecha' => now(),
                'hist_user_id' => $actor['actor_tipo'] === 'usuario' ? $actor['actor_id'] : null,
                'hist_user_nombre' => $actor['actor_nombre'],
                'hist_ip' => $peticion['ip'] ?? null,
                'hist_peticion' => $peticion['peticion'] ?? null,
            ]);

        } catch (\Throwable $e) {

            Log::error('No se pudo guardar el histórico de ' . $modelo->getTable() . ': ' . $e->getMessage());

            Auditoria::registrar(
                evento: 'historico_error',
                descripcion: 'No se pudo guardar el histórico de ' . $modelo->getTable() . ' #' . $modelo->getKey(),
                extra: ['error' => $e->getMessage()],
            );
        }
    }


    // todas las versiones guardadas de un registro (la mas nueva primero)
    public static function versiones(string $tabla, int|string $id)
    {
        $historica = self::nombre($tabla);

        if (! self::esquema()->hasTable($historica)) {
            return collect();
        }

        return DB::connection(self::CONEXION)->table($historica)
            ->where('id', $id)
            ->orderByDesc('hist_version')
            ->get();
    }


    // copia lo que hay ahora como version base (para no perder lo ya cargado)
    public static function copiarActual(string $tabla): int
    {
        self::sincronizarTabla($tabla);

        $historica = self::nombre($tabla);
        $columnas = self::columnas($historica);
        $total = 0;

        DB::table($tabla)->orderBy('id')->chunk(200, function ($filas) use ($tabla, $historica, $columnas, &$total) {

            foreach ($filas as $fila) {

                $fila = (array) $fila;

                $yaTiene = DB::connection(self::CONEXION)->table($historica)
                    ->where('id', $fila['id'])->where('hist_operacion', 'BASE')->exists();

                if ($yaTiene) {
                    continue;
                }

                $datos = array_intersect_key($fila, array_flip($columnas));
                unset($datos['remember_token']);

                DB::connection(self::CONEXION)->table($historica)->insert([
                    ...$datos,
                    'hist_operacion' => 'BASE',
                    'hist_version' => 0,
                    'hist_fecha' => now(),
                ]);

                $total++;
            }
        });

        return $total;
    }
}
