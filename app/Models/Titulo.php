<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Titulo extends Model
{
    protected $fillable = [
        'nombre',
        'estudio_id',
    ];
    public function estudio(){
        return $this->belongsTo(Estudio::class, 'estudio_id');
    }
}
