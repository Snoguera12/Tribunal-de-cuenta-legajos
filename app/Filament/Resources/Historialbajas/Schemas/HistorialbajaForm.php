<?php

namespace App\Filament\Resources\Historialbajas\Schemas;

use App\Models\Legajo;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class HistorialbajaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                /*Select::make('legajo_id')
                    ->label('Número de legajo')
                    ->searchable()
                    ->required()
                    ->options(Legajo::all()->pluck("num_legajo", "id"))
                    ->validationMessages([
                        "required" => "Requiere asociar un legajo.",
                    ])->extraInputAttributes([
                        'oninvalid' => "this.setCustomValidity('Requiere asociar a un legajo.')",
                        'oninput' => "this.setCustomValidity('')",
                    ]),*/
                Select::make('motivo')
                    ->label('Motivo de Legajo')
                    ->searchable()
                    ->required()
                    ->options([
                        0 => 'Renuncia',
                        1 => 'Despido',
                        2 => 'Vencimiento de Contrato',
                        3 => 'Jubilación',
                        4 => 'Fallecimiento',
                        5 => 'Incapacidad',
                        6 => 'Traslado'
                    ])
                    ->validationMessages([
                        "required" => "Requiere asociar un legajo.",
                    ])->extraInputAttributes([
                        'oninvalid' => "this.setCustomValidity('Requiere asociar a un legajo.')",
                        'oninput' => "this.setCustomValidity('')",
                    ]),
                DateTimePicker::make('fecha_baja')
                    ->required(),
            ]);
    }
}
