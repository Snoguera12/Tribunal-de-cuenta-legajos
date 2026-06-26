<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estudio extends Model
{
    protected $fillable = [
        'nombre',
        'institucion',
        'nivel_estudio',
        'fecha_fin',
        'persona_id',
    ];
    public function persona(){
        return $this->belongsTo(Persona::class, 'persona_id');
    }
    public function titulos()
    {
        return $this->hasMany(Titulo::class);
    }
}
