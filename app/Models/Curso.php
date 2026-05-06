<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Curso extends Model
{
    protected $fillable = [
        "nombre",
        "fecha",
        "horas",
        "persona_id",
    ];

    public function persona(){
        return $this->belongsTo(Persona::class);
    }
}
