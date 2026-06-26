<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Persona extends Model
{
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
}
