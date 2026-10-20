<?php

namespace App\Filament\Resources\Historialbajas\Schemas;

use App\Enums\MotivoBajaEnum;
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
            Select::make('motivo')
            ->label('Motivo de Legajo')
            ->searchable()
            ->required()
            ->options(MotivoBajaEnum::class)
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
