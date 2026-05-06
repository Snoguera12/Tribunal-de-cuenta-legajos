<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Familiar extends Model
{
    protected $fillable = [
        "nombre",
        "dni",
        "fecha_de_nacimiento",
        "estado",
    ];
}
