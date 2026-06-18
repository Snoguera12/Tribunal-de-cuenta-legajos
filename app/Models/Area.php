<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    protected $fillable = [
        "nombre",
        'descripcion',
        'fecha_creacion',
    ];

    public function oficinas()
    {
        return $this->hasMany(Oficina::class);
    }
}
