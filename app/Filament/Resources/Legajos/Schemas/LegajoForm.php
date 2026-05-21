<?php

namespace App\Filament\Resources\Legajos\Schemas;

use App\Models\Cargo;
use App\Models\Persona;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LegajoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('num_legajo')
                    ->label('Número de legajo')
                    ->required()
                    ->maxLength(16)
                    ->validationMessages([
                        "required" => "Requiere introducir el Número de legajo.",
                    ]),
                Select::make('persona_id')
                    ->label('Persona')
                    ->required()
                    ->searchable()
                    ->options(Persona::selectRaw('id, CONCAT(nombre, " ", apellido) AS nombre_completo')->pluck('nombre_completo', 'id'))
                    ->validationMessages([
                        "required" => "Requiere asociar una Persona.",
                    ]),
                Select::make("cargo_id")
                    ->label("Cargo")
                    ->required()
                    ->options(Cargo::all()->pluck("nombre", "id"))
                    ->validationMessages([
                        "required" => "Requiere asociar un cargo.",
                    ]),
                TextInput::make('categoria')
                    ->required()
                    ->maxLength(3)
                    ->validationMessages([
                        "required" => "Requiere introducir la categoría.",
                    ]),
                DatePicker::make('fecha_de_ingreso')
                    ->default(Carbon::now())
                    ->required()
                    ->validationMessages([
                        "required" => "Requiere introducir la Fecha de ingreso.",
                    ]),
            ]);
    }
}
