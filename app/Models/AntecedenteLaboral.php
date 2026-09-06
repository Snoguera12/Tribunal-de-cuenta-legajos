<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AntecedenteLaboral extends Model
{
    //protected $table = 'antecedentes_laborales';

    protected $fillable = [
        'persona_id',
        'empleador',
        'lugar_de_trabajo',
        'cargo',
        'fecha_inicio',
        'fecha_fin',
        'motivo_egreso',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }
}