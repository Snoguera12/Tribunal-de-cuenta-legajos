<?php

namespace App\Models\Concerns;

use App\Models\Auditoria;
use App\Services\HistoricoService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;


// "use RegistraAuditoria;" en un modelo: anota en la auditoria y guarda la version anterior en el historico
trait RegistraAuditoria
{

    public static function bootRegistraAuditoria(): void
    {

        static::created(function (Model $modelo) {

            $nuevos = $modelo->getAttributes();

            // si hay una transaccion abierta espera a que termine bien
            DB::afterCommit(function () use ($modelo, $nuevos) {
                Auditoria::registrar(
                    evento: 'creado',
                    descripcion: 'Creó ' . self::etiquetaAuditoria($modelo),
                    modelo: $modelo,
                    despues: $nuevos,
                );
            });
        });


        static::updated(function (Model $modelo) {

            $cambios = $modelo->getChanges();
            unset($cambios['updated_at']);

            if (empty($cambios)) {
                return;
            }

            $antes = [];
            foreach (array_keys($cambios) as $campo) {
                $antes[$campo] = $modelo->getRawOriginal($campo);
            }

            // fila completa como estaba antes del cambio
            $filaAnterior = $modelo->getRawOriginal();

            // un borrado suave llega aca como un cambio de deleted_at, lo maneja deleted
            if (array_key_exists('deleted_at', $cambios) && count($cambios) === 1) {
                return;
            }

            DB::afterCommit(function () use ($modelo, $cambios, $antes, $filaAnterior) {

                HistoricoService::guardar($modelo, 'UPDATE', $filaAnterior);

                Auditoria::registrar(
                    evento: 'modificado',
                    descripcion: 'Modificó ' . self::etiquetaAuditoria($modelo) . ' (' . implode(', ', array_keys($cambios)) . ')',
                    modelo: $modelo,
                    antes: $antes,
                    despues: $cambios,
                );

                if (method_exists($modelo, 'auditoriaEspecial')) {
                    $modelo->auditoriaEspecial($cambios, $antes);
                }
            });
        });


        static::deleted(function (Model $modelo) {

            $fila = $modelo->getAttributes();
            $definitivo = method_exists($modelo, 'isForceDeleting') ? $modelo->isForceDeleting() : true;

            DB::afterCommit(function () use ($modelo, $fila, $definitivo) {

                HistoricoService::guardar($modelo, $definitivo ? 'FORCE_DELETE' : 'DELETE', $fila);

                Auditoria::registrar(
                    evento: $definitivo ? 'eliminado_definitivo' : 'eliminado',
                    descripcion: 'Eliminó ' . self::etiquetaAuditoria($modelo) . ($definitivo ? ' (definitivo)' : ''),
                    modelo: $modelo,
                    antes: $fila,
                );
            });
        });


        // solo existe si el modelo usa SoftDeletes
        if (method_exists(static::class, 'restored')) {

            static::restored(function (Model $modelo) {
                DB::afterCommit(function () use ($modelo) {
                    Auditoria::registrar(
                        evento: 'restaurado',
                        descripcion: 'Restauró ' . self::etiquetaAuditoria($modelo),
                        modelo: $modelo,
                    );
                });
            });
        }

    }


    // ej: "Legajo #12 (1045)"
    protected static function etiquetaAuditoria(Model $modelo): string
    {
        $texto = class_basename($modelo) . ' #' . $modelo->getKey();

        foreach (['num_legajo', 'name', 'nombre'] as $campo) {

            $valor = $modelo->getAttribute($campo);

            if ($valor !== null && $valor !== '') {
                $texto .= ' (' . $valor . ')';
                break;
            }
        }

        return $texto;
    }
}
