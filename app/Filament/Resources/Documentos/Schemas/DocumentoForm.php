<?php

namespace App\Filament\Resources\Documentos\Schemas;

use App\Models\Legajo;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DocumentoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make("legajo_id")
                    ->label("Número de legajo")
                    ->options(Legajo::all()->pluck("número de legajo", "id")),
                TextInput::make('descripcion')
                    ->required(),
                TextInput::make('estado')
                    ->required(),
                TextInput::make('Tipo_de_documento')
                    ->required(),
                DatePicker::make('fecha_subida')
                    ->required(),
                
                
                
            ]);
    }
}
