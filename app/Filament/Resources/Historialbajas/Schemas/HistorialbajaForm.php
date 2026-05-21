<?php

namespace App\Filament\Resources\Historialbajas\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class HistorialbajaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('legajo_id')
                    ->required()
                    ->numeric(),
                TextInput::make('descripcion')
                    ->required(),
                DateTimePicker::make('fecha_baja')
                    ->required(),
            ]);
    }
}
