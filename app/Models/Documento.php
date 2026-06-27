<?php

namespace App\Models;

use App\Enums\TipodocEnum;
use Illuminate\Database\Eloquent\Model;

class Documento extends Model
{
    protected $casts = [
        'tipodoc' => TipodocEnum::class,
    ];
    protected $fillable = [
        'archivo',
        'descripcion',
        'tipodoc',
        'activo',
        'fecha_de_creacion',
        //'fecha_de_subida',
        'legajo_id',
    ];

    public function legajo(){
        return $this->belongsTo(Legajo::class, 'legajo_id');
    }
}
