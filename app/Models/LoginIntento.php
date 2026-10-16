<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


// estado de los intentos de clave de un usuario (no se audita: cambia en cada intento)
class LoginIntento extends Model
{

    protected $table = 'login_intentos';

    protected $guarded = [];


    protected function casts(): array
    {
        return [
            'permanente' => 'boolean',
            'ultimo_fallo_en' => 'datetime',
            'proximo_intento_en' => 'datetime',
            'bloqueado_hasta' => 'datetime',
        ];
    }
}
