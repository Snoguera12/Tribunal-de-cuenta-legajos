<?php

namespace App\Filament\Resources\Estudios\Schemas;

use App\Models\Persona;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EstudioForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('titulo')
                    ->required(),
                TextInput::make('institucion')
                    ->required(),
                DatePicker::make('fecha_inicio')
                    ->required(),
                DatePicker::make('fecha_fin')
                    ->required(),
                Select::make("persona_id")
                    ->label("Persona")
                    ->options(Persona::all()->pluck("nombre", "id")),
            ]);
    }
}
