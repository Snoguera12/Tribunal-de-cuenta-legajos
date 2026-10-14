<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Licencia extends Model
{
    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fecha_aprobacion' => 'date',
        'payload_original' => 'array',
    ];

    protected $fillable = [
        'legajo_id',
        'api_client_id',
        'id_solicitud',
        'tipo_licencia',
        'fecha_inicio',
        'fecha_fin',
        'dias_solicitados',
        'motivo',
        'nro_resolucion',
        'fecha_aprobacion',
        'payload_original',
    ];

    public function legajo()
    {
        return $this->belongsTo(Legajo::class, 'legajo_id');
    }

    public function apiClient()
    {
        return $this->belongsTo(ApiClient::class, 'api_client_id');
    }

    /**
     * ¿Un reenvío trae exactamente los mismos datos que ya guardamos?
     * Se usa para decidir entre 200 (reintento idéntico) y 409 (datos distintos).
     */
    public function tieneLosMismosDatos(array $datos): bool
    {
        return $this->tipo_licencia === $datos['tipo_licencia']
            && $this->fecha_inicio->toDateString() === $datos['fecha_inicio']
            && $this->fecha_fin->toDateString() === $datos['fecha_fin']
            && (int) $this->dias_solicitados === (int) $datos['dias_solicitados']
            && (string) $this->motivo === (string) ($datos['motivo'] ?? '')
            && (string) $this->nro_resolucion === (string) ($datos['nro_resolucion'] ?? '')
            && $this->fecha_aprobacion->toDateString() === $datos['fecha_aprobacion'];
    }

    /**
     * Cosas que conviene revisar con el otro grupo. Son solo informativas:
     * la licencia se guarda igual. Lista vacía = sin observaciones.
     *
     * Sobre los días: todavía no hay catálogo de tipos, así que no se sabe si
     * la licencia cuenta días corridos o hábiles. Por eso solo se marca lo que
     * no cierra en NINGUNO de los dos casos.
     *
     * @return array<int, string>
     */
    public function observaciones(): array
    {
        $observaciones = [];

        // 1) Legajo dado de baja
        if ($this->legajo && ! $this->legajo->isAlta()) {
            $observaciones[] = 'El legajo está dado de baja.';
        }

        // 2) Días solicitados vs. rango de fechas
        $dias = (int) $this->dias_solicitados;
        $corridos = (int) abs($this->fecha_inicio->diffInDays($this->fecha_fin)) + 1;

        $habiles = 0;
        for ($dia = $this->fecha_inicio->copy(); $dia->lte($this->fecha_fin); $dia->addDay()) {
            if ($dia->isWeekday()) {
                $habiles++;
            }
        }

        if ($dias > $corridos) {
            $observaciones[] = "Los días solicitados ({$dias}) superan los días del rango ({$corridos}).";
        } elseif ($dias < $corridos && $dias > $habiles) {
            $observaciones[] = "Los días solicitados ({$dias}) no coinciden con el rango ({$corridos} corridos, {$habiles} hábiles).";
        }

        // 3) Superposición con otras licencias del mismo legajo
        $solapadas = static::query()
            ->where('legajo_id', $this->legajo_id)
            ->whereKeyNot($this->getKey())
            ->whereDate('fecha_inicio', '<=', $this->fecha_fin->toDateString())
            ->whereDate('fecha_fin', '>=', $this->fecha_inicio->toDateString())
            ->count();

        if ($solapadas > 0) {
            $observaciones[] = "Se superpone con {$solapadas} licencia(s) del mismo legajo.";
        }

        return $observaciones;
    }
}
