<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Oficina extends Model
{
    protected $fillable = [
        'nombre',
        'descripcion',
        //'fecha_creacion',
        'area_id',
    ];
    public function area(){
        return $this->belongsTo(Area::class, 'area_id');
    }
}
