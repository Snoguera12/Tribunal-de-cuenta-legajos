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
                TextInput::make('nombre')
                    ->required(),
                TextInput::make('nivel')
                    ->required(),
                Select::make("persona_id")
                    ->label("Persona")
                    ->options(Persona::all()->pluck("nombre", "id")),
            ]);
    }
}
