<?php

namespace App\Models;

use App\Enums\NivelEstudioEnum;
use Illuminate\Database\Eloquent\Model;

class Estudio extends Model
{
    protected $casts = [
        'nivel_estudio' => NivelEstudioEnum::class,
    ];
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
