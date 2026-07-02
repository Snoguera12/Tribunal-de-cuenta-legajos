<?php

namespace App\Filament\Resources\Estudios\Schemas;

use App\Enums\NivelEstudioEnum;
use App\Models\Persona;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class EstudioForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
            Tabs::make('Tabs')
            ->columnSpanFull()
            ->tabs([
                Tab::make('estudio')
                ->label('Estudio')
                ->schema([
                    Select::make('persona_id')->label('Persona')
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
                    Select::make('nivel_estudio')->label('Nivel de Estudio')
                    ->required()
                    ->searchable()
                    ->live()
                    ->options(NivelEstudioEnum::class),

                    TextInput::make('institucion')->label('Institución')
                    ->required(fn (Get $get): bool => $get('nivel_estudio') !== null && $get('nivel_estudio') != NivelEstudioEnum::SinEstudio)
                    ->disabled(fn (Get $get): bool => $get('nivel_estudio') === null || $get('nivel_estudio') == NivelEstudioEnum::SinEstudio),

                    DatePicker::make('fecha_fin')->label('Fecha de Finalización')
                    ->required(fn (Get $get): bool => $get('nivel_estudio') !== null && $get('nivel_estudio') != NivelEstudioEnum::SinEstudio)
                    ->disabled(fn (Get $get): bool => $get('nivel_estudio') === null || $get('nivel_estudio') == NivelEstudioEnum::SinEstudio),

                ]),
                Tab::make('titulo')
                ->label('Títulos')
                ->visible(fn (Get $get): bool => $get('nivel_estudio') !== null && $get('nivel_estudio') != NivelEstudioEnum::SinEstudio)
                ->schema([
                    Repeater::make('titulo')
                    ->relationship('titulos')
                    ->schema([
                        TextInput::make('nombre')
                        ->label('Nombre del Título')
                        ->validationMessages([
                            "required" => "Requiere introducir el Nombre del Título.",
                        ])
                        ->extraInputAttributes([
                            'oninvalid' => "this.setCustomValidity('Requiere introducir el Nombre del Título.')",
                            'oninput' => "this.setCustomValidity('')",
                        ]),
                    ])
                ])
            ]),
        ]);
    }
}
