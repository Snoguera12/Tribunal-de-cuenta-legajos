<?php

namespace App\Filament\Resources\Cursos\Schemas;

use App\Models\Persona;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CursoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->label('Nombre del Curso')
                    ->required()
                    ->validationMessages([
                        "required" => "Requiere introducir el nombre del curso.",
                    ]),
                Select::make("persona_id")
                    ->label("Persona")
                    ->required()
                    ->searchable()
                    ->options(Persona::selectRaw('id, CONCAT(nombre, " ", apellido) AS nombre_completo')->pluck('nombre_completo', 'id'))
                    ->validationMessages([
                        "required" => "Requiere asociar a una Persona.",
                    ]),
                DatePicker::make('fecha')
                    ->required()
                    ->validationMessages([
                        "required" => "Requiere introducir la fecha.",
                    ]),
                TextInput::make('horas')
                    ->required()
                    ->numeric()
                    ->validationMessages([
                        "required" => "Requiere introducir las horas.",
                    ]),
            ]);
    }
}
