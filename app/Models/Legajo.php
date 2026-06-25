<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Legajo extends Model
{
    protected $attributes = [
        "estado" => true,
    ];

    protected $fillable = [
        "num_legajo",
        "fecha_de_ingreso",
        "estado",
        "persona_id",
        "categoria_id",
        "cargo_id",
        "area_id",
    ];

    public function persona(){
        return $this->belongsTo(Persona::class, 'persona_id');
    }
    public function categoria(){
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }
    public function cargo(){
        return $this->belongsTo(Cargo::class, 'cargo_id');
    }
    public function area(){
        return $this->belongsTo(Area::class, 'area_id');
    }
    public function documentos()
    {
        return $this->hasMany(Documento::class);
    }
}
