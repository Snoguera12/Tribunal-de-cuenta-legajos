<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

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
    public static function Opciones(): Collection
    {
        return self::selectRaw("id, nombre || ' ' || apellido || ' (DNI: ' || dni || ')' AS nombre_completo")->pluck('nombre_completo', 'id');
    }
}
