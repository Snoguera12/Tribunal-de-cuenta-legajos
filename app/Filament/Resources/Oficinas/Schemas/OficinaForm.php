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
                    ->label('Descripción de la Oficina')
                    ->validationMessages([
                        "required" => "Requiere introducir la Decripción de la Oficina.",
                    ])
                    ->extraInputAttributes([
                        'oninvalid' => "this.setCustomValidity('Requiere introducir la Decripción de la Oficina.')",
                        'oninput' => "this.setCustomValidity('')",
                    ]),
                Select::make('area_id')
                    ->options(Area::selectRaw("id, nombre ")->pluck('nombre', 'id'))
                    ->required()
                    ->searchable()
                    ->suffixAction(
                        Action::make('area_id')
                            ->icon('heroicon-m-plus')
                            ->form([
                                // Campo de archivo dentro del modal flotante
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
                                    ->label('Descripción de la Oficina')
                                    ->validationMessages([
                                        "required" => "Requiere introducir la Decripción de la Oficina.",
                                    ])
                                    ->extraInputAttributes([
                                        'oninvalid' => "this.setCustomValidity('Requiere introducir la Decripción de la Oficina.')",
                                        'oninput' => "this.setCustomValidity('')",
                                    ]),
                            ])
                            ->action(function (array $data): void {
                                // Filament ya procesó el archivo y guardó la ruta en $data['ruta_archivo']
                                $fecha_actual = Carbon::now();
                                Area::created([
                                    'nombre' => $data['nombre'],
                                    //'descripcion' => $data['descripcion'],
                                    //'fecha_creacion' => $fecha_actual,
                                ]);

                                Notification::make('area_id')
                                ->success()
                                ->title('Se a creado una nueva Área.')
                                //->body(fn () => $record->estado ? 'El registro ha sido cambiado a "Dado de alta" correctamente.' : 'El registro ha sido cambiado a "Dado de baja" correctamente.')
                                ->send();
                            })
                    ),
            ]);
    }
}
