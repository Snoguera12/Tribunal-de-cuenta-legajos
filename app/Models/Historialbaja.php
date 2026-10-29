<?php

namespace App\Models;

use App\Models\Concerns\RegistraAuditoria;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\MotivoBajaEnum;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

class Historialbaja extends Model
{
    use RegistraAuditoria;
    use SoftDeletes;

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
    public static function getFormSchema() : array{
        $resultado = [
            TextColumn::make('legajo.num_legajo')
            ->label("Número de legajo")
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false),

            TextColumn::make('legajo.persona.nombre')
            ->label("Nombre")
            ->sortable()
            ->searchable()
            ->toggleable(isToggledHiddenByDefault: false),

            TextColumn::make('legajo.persona.apellido')
            ->label("Apellido")
            ->sortable()
            ->searchable()
            ->toggleable(isToggledHiddenByDefault: false),

            TextColumn::make('legajo.persona.dni')
            ->label("DNI")
            ->searchable()
            ->toggleable(isToggledHiddenByDefault: false),

            TextColumn::make('motivo')
            ->label('Motivo de la baja')
            ->toggleable(isToggledHiddenByDefault: false),
            
            TextColumn::make('fecha_baja')
            ->dateTime('d/m/Y H:i:s')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false),
        ];
        return $resultado;
    }
}
