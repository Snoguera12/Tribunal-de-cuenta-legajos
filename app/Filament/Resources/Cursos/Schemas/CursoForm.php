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
                    ->label('Nombre de Curso')
                    ->required(),
                Select::make("persona_id")
                    ->label("Persona")
                    ->options(Persona::selectRaw('id, CONCAT(nombre, " ", apellido) AS nombre_completo')->pluck('nombre_completo', 'id')),
                DatePicker::make('fecha')
                    ->required(),
                TextInput::make('horas')
                    ->required()
                    ->numeric(),
            ]);
    }
}
