<?php

namespace App\Filament\Resources\Personas\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PersonaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->required()
                    ->validationMessages([
                        "required" => "Requiere introducir el Nombre.",
                    ])
                    ->extraInputAttributes([
                        'oninvalid' => "this.setCustomValidity('Por favor, introducir el Nombre.')",
                        'oninput' => "this.setCustomValidity('')",
                    ]),
                TextInput::make('apellido')
                    ->required()
                    ->validationMessages([
                        "required" => "Requiere introducir el Apellido.",
                    ])
                    ->extraInputAttributes([
                        'oninvalid' => "this.setCustomValidity('Por favor, introducir el Apellido.')",
                        'oninput' => "this.setCustomValidity('')",
                    ]),
                TextInput::make('dni')
                    ->label("DNI")
                    ->numeric()
                    ->unique()
                    ->minValue(1)
                    ->rules(['digits_between:1,9'])
                    ->required()
                    ->validationMessages([
                        "required" => "Requiere introducir el DNI.",
                        "unique" => "Ya se registró el DNI."
                    ])
                    ->extraInputAttributes([
                        'oninvalid' => "this.setCustomValidity('Por favor, introducir el DNI.')",
                        'oninput' => "this.setCustomValidity('')",
                    ]),
                TextInput::make('cuil')
                    ->label("CUIL")
                    ->numeric()
                    ->unique()
                    ->minValue(1)
                    ->rules(['digits_between:1,12'])
                    ->required()
                    ->validationMessages([
                        "required" => "Requiere introducir el CUIL.",
                        "unique" => "Ya se registró el CUIL."
                    ])
                    ->extraInputAttributes([
                        'oninvalid' => "this.setCustomValidity('Por favor, introducir el CUIL.')",
                        'oninput' => "this.setCustomValidity('')",
                    ]),
                TextInput::make('email')
                    ->label('Correo Electróico')
                    ->email()
                    ->extraInputAttributes([
                        'oninvalid' => "this.setCustomValidity('Por favor, escribir correctamente el correo electrónico.')",
                        'oninput' => "this.setCustomValidity('')",
                    ]),
                Select::make('estado_civil')
                    ->label('Estado Civil')
                    ->options([
                        0 => 'Soltero/a',
                        1 => 'Casado/a',
                        2 => 'Viúdo/a',
                    ])
                    ->required()
                    ->validationMessages([
                        "required" => "Requiere selecionar el estado civil.",
                    ])
                    ->extraInputAttributes([
                        'oninvalid' => "this.setCustomValidity('Por favor, selecione el género.')",
                        'oninput' => "this.setCustomValidity('')",
                    ]),
                Select::make("genero")
                    ->label("Género")
                    ->options([
                        0 => "Femenino",
                        1 => "Masculino",
                        2 => 'Otro',
                    ])
                    ->required()
                    ->validationMessages([
                        "required" => "Requiere selecionar el género.",
                    ])
                    ->extraInputAttributes([
                        'oninvalid' => "this.setCustomValidity('Por favor, selecione el género.')",
                        'oninput' => "this.setCustomValidity('')",
                    ]),
                DatePicker::make('fecha_de_nacimiento')
                ->maxDate(now()->subYears(18)->toDateString()) // Máximo hace 18 años
                ->rules(['date', 'before_or_equal:' . now()->subYears(18)->toDateString()])
                ->helperText('La persona tiene que ser mayor de edad.')
                ->required()
                ->validationMessages([
                    "required" => "Requiere introducir la Fecha de nacimiento.",
                ])
                ->extraInputAttributes([
                    'oninvalid' => "this.setCustomValidity('Por favor, ingrese la fecha de nacimiento.')",
                    'oninput' => "this.setCustomValidity('')",
                ]),
                TextInput::make('domicilio'),
                TextInput::make('telefono')
                    ->label('Teléfono')
                    ->tel(),
                TextInput::make('telefono_emergencia')
                    ->label('Teléfono de emergencia')
                    ->tel(),
            ]);
    }
}
