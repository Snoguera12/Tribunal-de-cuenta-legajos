<?php

namespace App\Models;

use App\Enums\MotivoBajaEnum;
use Illuminate\Database\Eloquent\Model;

class Historialbaja extends Model
{
    protected $casts = [
        "motivo" => MotivoBajaEnum::class,
    ];
    protected $fillable = [
        "legajo_id",
        "user_id",
        "motivo",
        "fecha_baja",
    ];
    public function legajo(){
        //return $this->hasMany(Cargo::class);
        return $this->belongsTo(Legajo::class, "legajo_id");
    }
}
