<?php

namespace App\Models;

use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    protected $fillable = [
        "nombre",
        'descripcion',
        'fecha_creacion',
    ];

    public function oficinas()
    {
        return $this->hasMany(Oficina::class);
    }
    public function legajos()
    {
        return $this->hasMany(Legajo::class);
    }
    public static function getAreas() : array{
        return Area::pluck('nombre', 'id')->toArray();
    }
    public static function getFormSchema() : array{
        $resultado = [
            TextInput::make('nombre')
            ->label('Nombre del Área')
            ->required()
            ->validationMessages([
                "required" => "Requiere introducir el Nombre del Área.",
            ])
            ->extraInputAttributes([
                'oninvalid' => "this.setCustomValidity('Requiere introducir el Nombre del Área.')",
                'oninput' => "this.setCustomValidity('')",
            ]),
        ];
        return $resultado;
    }
    public static function getOutSchema() : array{
        $resultado = [
            TextColumn::make('nombre')
            ->label('Nombre del Área')
            ->sortable()
            ->searchable(),

            TextColumn::make('legajos_count')
            ->label('Empleados Asociados')
            ->counts('legajos')
            ->sortable(),
        ];
        return $resultado;
    }
}
