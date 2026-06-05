<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Documento extends Model
{
    protected $fillable = [
        'tipodoc',
        'archivo',
        'descripcion',
        'activo',
        'fecha_de_creacion',
        'fecha_de_subida',
        'legajo_id',
    ];

    public function persona(){
        return $this->belongsTo(Legajo::class, 'legajo_id');
    }
}
