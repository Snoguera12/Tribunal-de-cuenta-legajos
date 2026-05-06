<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Documento extends Model
{
    protected $fillable = [
        "Tipo_de_documento",
        "fecha_subida",
        "estado",
        "descripcion",
        "legajo_id",
    ];

    public function legajo(){
        return $this->belongsTo(Legajo::class);
    }
}
