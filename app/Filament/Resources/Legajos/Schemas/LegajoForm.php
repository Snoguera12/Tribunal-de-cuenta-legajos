<?php

namespace App\Filament\Resources\Legajos\Schemas;

use App\Models\Cargo;
use App\Models\Persona;
use Filament\Forms\Components\DateTimePicker;
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
                        'required' => 'Requiere introducir el Número de legajo.',
                    ])
                    ->extraInputAttributes([
                        'oninvalid' => "this.setCustomValidity('Requiere introducir el Número de legajo.')",
                        'oninput' => "this.setCustomValidity('')",
                    ]),
                Select::make('persona_id')
                    ->label('Persona')
                    ->required()
                    ->searchable()
                    ->options(Persona::selectRaw("id, nombre || ' ' || apellido || ' (DNI: ' || dni || ')' AS nombre_completo")->pluck('nombre_completo', 'id'))
                    ->validationMessages([
                        "required" => "Requiere asociar una Persona.",
                    ])
                    ->extraInputAttributes([
                        'oninvalid' => "this.setCustomValidity('Requiere asociar a una Persona.')",
                        'oninput' => "this.setCustomValidity('')",
                    ]),
                Select::make("cargo_id")
                    ->label("Cargo")
                    ->searchable()
                    ->required()
                    ->options(Cargo::all()->pluck("nombre", "id"))
                    ->validationMessages([
                        "required" => "Requiere asociar un cargo.",
                    ])->extraInputAttributes([
                        'oninvalid' => "this.setCustomValidity('Requiere asociar a un Cargo.')",
                        'oninput' => "this.setCustomValidity('')",
                    ]),
                TextInput::make('categoria')
                    ->required()
                    ->maxLength(3)
                    ->validationMessages([
                        "required" => "Requiere introducir la categoría.",
                    ])->extraInputAttributes([
                        'oninvalid' => "this.setCustomValidity('Requiere introducir a una Categoría.')",
                        'oninput' => "this.setCustomValidity('')",
                    ]),
                DateTimePicker::make('fecha_de_ingreso')
                    ->validationMessages([
                        "required" => "Requiere introducir la Fecha de ingreso.",
                    ])
                    ->native(false)
                    ->helperText('Si no introduce la fecha, se asigna la fecha de hoy.'),
            ]);
    }
}
