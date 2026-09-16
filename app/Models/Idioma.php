<?php

namespace App\Models;

use App\Enums\IdiomaNivelEnum;
use Illuminate\Database\Eloquent\Model;

class Idioma extends Model
{
    protected $casts = [
        'nivel' => IdiomaNivelEnum::class,
    ];
    protected $fillable = [
        'persona_id',
        'idioma',
        'nivel',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }
}
