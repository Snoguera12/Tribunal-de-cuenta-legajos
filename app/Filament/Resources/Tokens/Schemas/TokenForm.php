<?php

namespace App\Filament\Resources\Tokens\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TokenForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
        ->components([
            TextInput::make('nombre')
            ->label("Nombre")
            ->autocomplete(false)
            ->required(),
        ]);
    }
}
