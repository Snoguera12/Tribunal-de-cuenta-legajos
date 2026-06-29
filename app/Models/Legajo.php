<?php

namespace App\Models;

use App\Enums\EstadoLegajoEnum;
use App\Enums\TipoContratoEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class Legajo extends Model
{
    protected $attributes = [
        "estado" => true,
    ];
    protected $casts = [
        "estado" => EstadoLegajoEnum::class,
        "tipo_contrato" => TipoContratoEnum::class,
    ];
    protected $fillable = [
        "num_legajo",
        "fecha_de_ingreso",
        "estado",
        "tipo_contrato",
        "persona_id",
        "categoria_id",
        "cargo_id",
        "area_id",
    ];
    public function histrorial(){
        return $this->hasMany(Historialbaja::class);
    }
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
    public function isAlta()
    {
        return $this->estado == EstadoLegajoEnum::Alta;
    }
    public function getIcon()
    {
        return $this->isAlta() ? Heroicon::CheckCircle : Heroicon::XCircle;
    }
    public function getColor()
    {
        return $this->isAlta() ? 'success' : 'danger';
    }
}
