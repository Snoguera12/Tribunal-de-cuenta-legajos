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
        "sexo",
        "fecha_de_nacimiento",
        "domicilio",
        "telefono",
        "telefono_emergencia"
    ];
}
