<?php

namespace App\Models;

use App\Models\Concerns\NoSeBorra;
use Illuminate\Database\Eloquent\Model;


class Incidente extends Model
{
    // un incidente nunca se borra: se cierra o se descarta
    use NoSeBorra;

    protected $table = 'incidentes';

    protected $guarded = [];


    protected function casts(): array
    {
        return [
            'detalle' => 'array',
            'resuelto_en' => 'datetime',
        ];
    }


    public static function estados(): array
    {
        return [
            'abierto' => 'Abierto',
            'en_revision' => 'En revisión',
            'resuelto' => 'Resuelto',
            'descartado' => 'Descartado',
        ];
    }


    public static function tipos(): array
    {
        return [
            'fuerza_bruta' => 'Fuerza bruta (misma IP)',
            'ataque_cuenta' => 'Ataque a una cuenta',
            'login_tras_fallos' => 'Ingreso tras varios fallos',
            'acceso_no_autorizado' => 'Accesos no autorizados repetidos',
            'borrado_masivo' => 'Borrado masivo',
            'archivo_malicioso' => 'Archivo sospechoso',
            'cambio_privilegios' => 'Cambio de privilegios',
            'fallas_repetidas' => 'Errores repetidos del sistema',
            'backup_fallido' => 'Backup fallido',
            'backup_desactualizado' => 'Backup desactualizado',
            'auditoria_alterada' => 'Auditoría alterada',
            'cuenta_bloqueada' => 'Usuario bloqueado temporalmente',
            'cuenta_bloqueada_permanente' => 'Usuario bloqueado definitivamente',
            'sesion_compartida' => 'Posible clave compartida (ingresos duplicados)',
        ];
    }
}
