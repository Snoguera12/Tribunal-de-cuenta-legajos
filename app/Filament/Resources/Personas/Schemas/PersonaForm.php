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
                    ->required(),
                TextInput::make('apellido')
                    ->required(),
                TextInput::make('dni')
                    ->label("DNI")
                    ->required(),
                TextInput::make('cuil')
                    ->label("CUIL")
                    ->required(),
                TextInput::make('email')
                    ->label('Correo Electróico')
                    ->email(),
                Select::make("sexo")
                    ->label("Sexo")
                    ->options([
                        "0" => "Femenino",
                        "1" => "Masculino",
                    ])
                    ->required(),
                DatePicker::make('fecha_de_nacimiento')
                ->maxDate(now()->subYears(18)->toDateString()) // Máximo hace 18 años
                ->rules(['date', 'before_or_equal:' . now()->subYears(18)->toDateString()])
                ->helperText('La persona tiene que ser mayor de edad.')
                    ->required(),
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
