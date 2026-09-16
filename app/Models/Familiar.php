<?php

namespace App\Models;

use App\Enums\FamiliarViveEnum;
use App\Enums\ParentescoEnum;
use Illuminate\Database\Eloquent\Model;

class Familiar extends Model
{
    protected $casts = [
        'parentesco' => ParentescoEnum::class,
        'vive' => FamiliarViveEnum::class,
    ];
    protected $fillable = [
        'nombre',
        'apellido',
        'dni',
        'fecha_de_nacimiento',
        'parentesco',
        'vive',
        'persona_id',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }
}
