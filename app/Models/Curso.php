<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Curso extends Model
{
    protected $fillable = [
        'persona_id',
        'nombre',
        'institucion',
        'duracion',
        'fecha',
        'tiene_certificado',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }
}
