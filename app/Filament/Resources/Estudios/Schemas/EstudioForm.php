<?php

namespace App\Filament\Resources\Estudios\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

class EstudioForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->label('Nombre del Estudio'),
                TextInput::make('institucion')
                    ->label('Institución')
                    ->required(),
                Select::make('nivel_estudio')
                    ->label('Nivel de Estudio')
                    ->required()
                    ->searchable()
                    ->options([
                        1 => 'Primaria',
                        2 => 'Secundaria',
                        3 => 'Terciario',
                        4 => 'Universitario',
                        5 => 'Doctorado',
                        6 => 'Maestría'
                    ]),
                DatePicker::make('fecha_inicio')
                    ->label('Fecha de Inicialización')
                    ->required(),
                DatePicker::make('fecha_fin')
                    ->label('Fecha de Finalización')
                    ->required(),
                Toggle::make('activo')
                    ->label('Activo'),
            ]);
    }
}
