<?php

namespace App\Models;

use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

class Cargo extends Model
{
    protected $fillable = [
        "nombre"
    ];
    public function legajos()
    {
        return $this->hasMany(Legajo::class);
    }
    public static function getCargos(): array
    {
        return Cargo::pluck('nombre', 'id')->toArray();
    }
    public static function getFormSchema() : array{
        $resultado = [
            TextInput::make('nombre')->required(),
        ];
        return $resultado;
    }
    public static function getOutSchema() : array{
        $resultado = [
            TextColumn::make('nombre')
            ->label('Nombre del Cargo')
            ->sortable()
            ->searchable(),

            /*TextColumn::make('cargos_count')
            ->label('Empleados Asociados')
            ->counts('legajos')
            ->badge()
            ->sortable(),*/
        ];
        return $resultado;
    }
}
