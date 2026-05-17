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
                    ->required()
                    ->searchable()
                    ->options(Legajo::all()->where('estado', true)->pluck("num_legajo", "id")),
                TextInput::make('descripcion')
                ->label('Descripción'),
                TextInput::make('estado')
                    ->required()
                    ->validationMessages([
                        "required" => "Requiere introducir un estado del documento.",
                    ]),
                TextInput::make('Tipo_de_documento')
                    ->required()
                    ->validationMessages([
                        "required" => "Requiere introducir el tipo de documento.",
                    ]),
                DatePicker::make('fecha_subida')
                    ->required()
                    ->validationMessages([
                        "required" => "Requiere introducir la fecha de subida.",
                    ]),
            ]);
    }
}
