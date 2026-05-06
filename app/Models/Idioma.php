<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Idioma extends Model
{
    protected $fillable = [
        "nombre",
        "nivel",
        "persona_id",
    ];

    public function persona(){
        return $this->belongsTo(Persona::class);
    }
}
