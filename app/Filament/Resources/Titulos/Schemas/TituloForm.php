<?php

namespace App\Filament\Resources\Titulos\Schemas;

use App\Models\Estudio;
use App\Models\Legajo;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TituloForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->label('Nombre del Título')
                    ->required()
                    ->validationMessages([
                        "required" => "Requiere introducir el Nombre del Área.",
                    ])
                    ->extraInputAttributes([
                        'oninvalid' => "this.setCustomValidity('Requiere introducir el Nombre del Área.')",
                        'oninput' => "this.setCustomValidity('')",
                    ]),
                Select::make("estudio_id")
                    ->label("Estudio")
                    ->searchable()
                    ->required()
                    ->options(Estudio::selectRaw("id, institucion")->pluck('institucion', 'id')),
                Select::make("legajo_id")
                    ->label("Legajo")
                    ->searchable()
                    ->required()
                    ->options(Legajo::selectRaw("id, num_legajo")->pluck('num_legajo', 'id'))
            ]);
    }
}
