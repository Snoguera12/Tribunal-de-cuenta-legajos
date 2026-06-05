<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estudio extends Model
{
    protected $fillable = [
        'institucion',
        'nivel_estudio',
        'activo',
        'fecha_inicio',
        'fecha_fin',
        'persona_id',
    ];
    public function persona(){
        return $this->belongsTo(Persona::class, 'persona_id');
    }
}
