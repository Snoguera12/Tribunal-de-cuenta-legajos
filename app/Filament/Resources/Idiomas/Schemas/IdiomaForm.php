<?php

namespace App\Filament\Resources\Idiomas\Schemas;

use App\Models\Persona;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class IdiomaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                 Select::make('persona_id')
                    ->label('Persona')
                    ->required()
                    ->searchable()
                    ->options(Persona::selectRaw('id, CONCAT(nombre, " ", apellido) AS nombre_completo')->pluck('nombre_completo', 'id'))
                    ->validationMessages([
                        "required" => "Requiere asociar a una Persona.",
                    ]),
                TextInput::make('nombre')
                    ->label('Idioma')
                    ->required()
                    ->validationMessages([
                        "required" => "Requiere introducir el idioma.",
                    ]),
                TextInput::make('nivel')
                    ->required()
                    ->validationMessages([
                        "required" => "Requiere introducir el nivel.",
                    ]),
               
                    
            ]);
    }
}
