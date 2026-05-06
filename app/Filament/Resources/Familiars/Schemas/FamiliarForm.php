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
                    ->required(),
                TextInput::make('dni')
                    ->required(),
                DatePicker::make('fecha_de_nacimiento')
                    ->required(),
                TextInput::make('estado')
                    ->required(),
            ]);
    }
}
