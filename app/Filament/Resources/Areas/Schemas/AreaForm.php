<?php

namespace App\Filament\Resources\Areas\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AreaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->label('Nombre del Área')
                    ->required()
                    ->validationMessages([
                        "required" => "Requiere introducir el Nombre del Área.",
                    ])
                    ->extraInputAttributes([
                        'oninvalid' => "this.setCustomValidity('Requiere introducir el Nombre del Área.')",
                        'oninput' => "this.setCustomValidity('')",
                    ]),
            ]);
    }
}
