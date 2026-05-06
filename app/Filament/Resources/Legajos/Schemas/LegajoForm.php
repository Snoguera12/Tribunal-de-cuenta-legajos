<?php

namespace App\Filament\Resources\Legajos\Schemas;

use App\Models\Cargo;
use App\Models\Persona;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LegajoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('número de legajo')
                    ->required(),
                DatePicker::make('fecha_de_ingreso')
                    ->required(),

                Select::make("cargo_id")
                    ->label("Cargo")
                    ->options(Cargo::all()->pluck("nombre", "id")),
                TextInput::make('categoria')
                    ->required(),
                Select::make("persona_id")
                    ->label("Persona")
                    ->options(Persona::all()->pluck("nombre", "id")),
            ]);
    }
}
