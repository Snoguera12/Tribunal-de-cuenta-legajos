<?php

namespace App\Filament\Resources\Areas\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;

class AreaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tabs')
                    ->tabs([
                        Tabs\Tab::make('Tab 1')
                            ->label('Área')
                            ->schema([
                                TextInput::make('nombre')
                                ->label('Nombre del Área')
                                ->required()
                                ->validationMessages([
                                    "required" => "Requiere introducir el Nombre del Área.",
                                ])
                                ->extraInputAttributes([
                                    'oninvalid' => "this.setCustomValidity('Requiere introducir el Nombre del Área.')",
                                    'oninput' => "this.setCustomValidity('')",
                                ]),
                            ]),
                        Tabs\Tab::make('Tab 2')
                            ->label('Oficinas')
                            ->schema([
                                Repeater::make('oficina')
                                ->relationship('oficinas')
                                ->schema([
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
                                ])
                                
                            ]),
                        /*Tabs\Tab::make('Tab 3')
                            ->schema([
                                // ...
                            ]),*/
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
