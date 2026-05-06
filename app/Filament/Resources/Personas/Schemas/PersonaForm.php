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
                    ->required(),
                TextInput::make('cuil')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                Select::make("sexo")
                    ->label("Sexo")
                    ->options(["F", "M"]),
                DatePicker::make('fecha_de_nacimiento')
                    ->required(),
                TextInput::make('domicilio'),
                TextInput::make('telefono')
                    ->tel(),
                TextInput::make('telefono_emergencia')
                    ->tel(),
            ]);
    }
}
