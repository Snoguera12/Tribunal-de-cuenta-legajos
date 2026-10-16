<?php

namespace App\Models;

use App\Models\Concerns\RegistraAuditoria;
use Illuminate\Database\Eloquent\Model;

class Oficina extends Model
{
    use RegistraAuditoria;

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
