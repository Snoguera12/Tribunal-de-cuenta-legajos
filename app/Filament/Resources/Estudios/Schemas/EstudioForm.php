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
                    ->label('Título')
                    ->required(),
                TextInput::make('institucion')
                    ->label('Institución')
                    ->required(),
                DatePicker::make('fecha_inicio')
                    ->label('Fecha de inicio')
                    ->required(),
                DatePicker::make('fecha_fin')
                    ->required(),
                Select::make('persona_id')
                    ->label('Persona')
                    ->options(Persona::selectRaw('id, CONCAT(nombre, " ", apellido) AS nombre_completo')->pluck('nombre_completo', 'id')),
            ]);
    }
}
