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
                    ->required(),
                DatePicker::make('fecha')
                    ->required(),
                TextInput::make('horas')
                    ->required()
                    ->numeric(),
                Select::make("persona_id")
                    ->label("Persona")
                    ->options(Persona::all()->pluck("nombre", "id")),
            ]);
    }
}
