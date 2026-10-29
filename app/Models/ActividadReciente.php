<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Ultimos agentes y usuarios que una persona de RRHH (o el administrador) vio o modifico.
 * Una fila por usuario + registro: se actualiza cada vez, no crece sin limite.
 */
class ActividadReciente extends Model
{
    protected $table = 'actividad_reciente';

    protected $fillable = ['user_id', 'tipo', 'registro_id', 'visto_en', 'modificado_en'];

    protected $casts = [
        'visto_en' => 'datetime',
        'modificado_en' => 'datetime',
    ];

    public const TIPO_AGENTE = 'agente';
    public const TIPO_USUARIO = 'usuario';

    // solo cuenta lo que hace RRHH o un administrador; nunca rompe lo que se estaba haciendo
    public static function registrar(string $tipo, int $registroId, string $accion): void
    {
        $actor = auth()->user();

        // por la API el "usuario" es un cliente de la API, no una persona de RRHH
        if (! ($actor instanceof User) || ! $actor->isAdmin_RRHH() || ! in_array($accion, ['visto', 'modificado'], true)) {
            return;
        }

        // lo que uno se hace a si mismo (clave, sesion) no es actividad sobre un agente
        if ($tipo === self::TIPO_USUARIO && (int) $registroId === (int) $actor->id) {
            return;
        }

        try {
            $fila = static::firstOrNew(['user_id' => $actor->id, 'tipo' => $tipo, 'registro_id' => $registroId]);
            $fila->{$accion === 'visto' ? 'visto_en' : 'modificado_en'} = now();
            $fila->updated_at = now();
            $fila->save();
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function usuarioDeRrhh()
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'registro_id')->withTrashed();
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'registro_id')->withTrashed();
    }

    // lo ultimo que hizo: ver o modificar
    public function getUltimaAccionAttribute(): string
    {
        if ($this->modificado_en && (! $this->visto_en || $this->modificado_en->gte($this->visto_en))) {
            return 'Modificó';
        }

        return 'Vio';
    }

    // numero que se muestra: N° de legajo del agente (el de alta, si no el ultimo) o id del usuario
    public function getNumeroAttribute(): string
    {
        if ($this->tipo === self::TIPO_USUARIO) {
            return 'Usuario #' . $this->registro_id;
        }

        $legajo = Legajo::query()
            ->where('persona_id', $this->registro_id)
            ->orderByDesc('estado')
            ->orderByDesc('fecha_de_ingreso')
            ->first();

        return $legajo ? 'Legajo ' . $legajo->num_legajo : 'Sin legajo';
    }

    public function getNombreAttribute(): string
    {
        if ($this->tipo === self::TIPO_USUARIO) {
            return $this->usuario?->name ?? 'Usuario eliminado';
        }

        $persona = $this->persona;

        return $persona ? trim($persona->apellido . ', ' . $persona->nombre) : 'Agente eliminado';
    }
}
