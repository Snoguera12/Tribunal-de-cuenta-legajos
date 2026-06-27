<?php

namespace App\Models;

use App\Enums\EstadoCivilEnum;
use App\Enums\GeneroEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Persona extends Model
{
    protected $casts = [
        'genero' => GeneroEnum::class,
        'estado_civil' => EstadoCivilEnum::class,
    ];
    protected $fillable = [
        "nombre",
        "apellido",
        "dni",
        "cuil",
        "email",
        "genero",
        "estado_civil",
        "fecha_de_nacimiento",
        "domicilio",
        "telefono",
        "telefono_emergencia",
    ];
    
    public function legajos()
    {
        return $this->hasMany(Legajo::class);
    }
    public function estudios()
    {
        return $this->hasMany(Estudio::class);
    }
    
    public function estudioPrioritario()
    {
        return $this->hasOne(Estudio::class)
            ->orderByRaw("CASE 
                WHEN nivel_estudio = 'Universitario' THEN 1
                WHEN nivel_estudio = 'Terciario' THEN 2
                WHEN nivel_estudio = 'Secundario' THEN 3
                WHEN nivel_estudio = 'Primario' THEN 4
                ELSE 5 
            END")->latestOfMany(); // Garantiza que devuelva un solo registro compatible con Section::relationship
    }
    public static function Opciones(): Collection
    {
        return self::selectRaw("id, nombre || ' ' || apellido || ' (DNI: ' || dni || ')' AS nombre_completo")->pluck('nombre_completo', 'id');
    }
}
