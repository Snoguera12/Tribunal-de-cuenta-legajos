<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Institucion extends Model
{
    protected $fillable = [
        'nombre',
        'nombre_otro',
    ];
    public function estudios()
    {
        return $this->hasMany(Estudio::class);
    }
}
