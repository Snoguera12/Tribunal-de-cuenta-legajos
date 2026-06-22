<?php

namespace App\Filament\Resources\Estudios\Schemas;

use App\Models\Persona;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

class EstudioForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tabs')
                    ->tabs([
                        Tabs\Tab::make('estudio')
                            ->label('Estudio')
                            ->schema([
                                Select::make('persona_id')
                                    ->label('Persona')
                                    ->required()
                                    ->searchable()
                                    ->options(Persona::selectRaw("id, nombre || ' ' || apellido || ' (DNI: ' || dni || ')' AS nombre_completo")->pluck('nombre_completo', 'id'))
                                    ->validationMessages([
                                        "required" => "Requiere asociar una Persona.",
                                    ])
                                    ->extraInputAttributes([
                                        'oninvalid' => "this.setCustomValidity('Requiere asociar a una Persona.')",
                                        'oninput' => "this.setCustomValidity('')",
                                    ]),
                                /*TextInput::make('nombre')
                                    ->label('Nombre del Estudio'),*/
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
                                DatePicker::make('fecha_fin')
                                    ->label('Fecha de Finalización'),
                            ]),
                        Tabs\Tab::make('titulo')
                            ->label('Títulos')
                            ->schema([
                                Repeater::make('titulo')
                                ->relationship('titulos')
                                ->schema([
                                    TextInput::make('nombre')
                                        ->label('Nombre del Título')
                                        ->required()
                                        ->validationMessages([
                                            "required" => "Requiere introducir el Nombre del Título.",
                                        ])
                                        ->extraInputAttributes([
                                            'oninvalid' => "this.setCustomValidity('Requiere introducir el Nombre del Título.')",
                                            'oninput' => "this.setCustomValidity('')",
                                        ]),
                                ])
                            ])

                ])
                ->columnSpanFull(),
            ]);
    }
}
