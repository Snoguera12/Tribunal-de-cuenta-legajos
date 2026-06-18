<?php

namespace App\Filament\Resources\Oficinas\Schemas;

use App\Models\Area;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class OficinaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->label('Nombre de la Oficina')
                    ->required()
                    ->validationMessages([
                        "required" => "Requiere introducir el Nombre de la Oficina.",
                    ])
                    ->extraInputAttributes([
                        'oninvalid' => "this.setCustomValidity('Requiere introducir el Nombre de la Oficina.')",
                        'oninput' => "this.setCustomValidity('')",
                    ]),
                Textarea::make('descripcion')
                    ->required()
                    ->label('Descripción de la Oficina')
                    ->validationMessages([
                        "required" => "Requiere introducir la Descripción de la Oficina.",
                    ])
                    ->extraInputAttributes([
                        'oninvalid' => "this.setCustomValidity('Requiere introducir la Descripción de la Oficina.')",
                        'oninput' => "this.setCustomValidity('')",
                    ]),
                Select::make('area_id')
                    ->label('Área')
                    ->options(Area::selectRaw("id, nombre ")->pluck('nombre', 'id'))
                    ->required()
                    ->searchable(),
            ]);
    }
}
