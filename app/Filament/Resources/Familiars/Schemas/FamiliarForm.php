<?php

namespace App\Filament\Resources\Familiars\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class FamiliarForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->required()
                    ->validationMessages([
                        "required" => "Requiere introducir el nombre de la familia.",
                    ]),
                TextInput::make('dni')
                    ->label('DNI')
                    ->unique()
                    ->required()
                    ->validationMessages([
                        'required' => 'Requiere introducir un DNI.',
                        'unique' => 'Ya se asignó el DNI.'
                    ]),
                DatePicker::make('fecha_de_nacimiento')
                    ->required()
                    ->validationMessages([
                        "required" => "Requiere introducir la fecha de nacimiento.",
                    ]),
                TextInput::make('estado')
                    ->required()
                    ->validationMessages([
                        "required" => "Requiere introducir el estado.",
                    ]),
            ]);
    }
}
