<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Legajo extends Model
{
    protected $attributes = [
        "estado" => true,
    ];

    protected $fillable = [
        "número de legajo",
        "fecha_de_ingreso",
        "estado",
        "cargo_id",
        "categoria",
        "persona_id",
    ];

    public function persona(){
        //return $this->hasMany(Persona::class);
        return $this->belongsTo(Persona::class);
    }
    public function cargo(){
        //return $this->hasMany(Cargo::class);
        return $this->belongsTo(Cargo::class);
    }
}
