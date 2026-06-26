<?php

namespace App\Filament\Resources\Personas\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;

class PersonaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tabs')
                    ->columns(2)
                    ->columnSpanFull()
                    ->tabs([
                        Tabs\Tab::make('Tab 1')
                            ->label('Persona')
                            ->schema([
                                Section::make('Datos Personales')
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('nombre')
                                            ->label('Nombre'),
                                        TextEntry::make('apellido')
                                            ->label('Apellido'),
                                        TextEntry::make('dni')
                                            ->label('DNI'),
                                        TextEntry::make('cuil')
                                            ->label('CUIL'),
                                        TextEntry::make('email')
                                            ->label('Correo Electrónico')
                                            ->placeholder('-'),
                                        TextEntry::make('genero')
                                            ->label('Género')
                                            ->numeric()
                                            ->placeholder('-')
                                            ->formatStateUsing(fn (int $state): string => match ($state) {
                                                0 => 'Femenino',
                                                1 => 'Masculino',
                                                2 => 'Otro',
                                                default => 'Desconocido',
                                            }),
                                        TextEntry::make('estado_civil')
                                            ->label('Estado Civil')
                                            ->numeric()
                                            ->placeholder('-')
                                            ->formatStateUsing(fn (int $state): string => match ($state) {
                                                0 => 'Soltero/a',
                                                1 => 'Casado/a',
                                                2 => 'Viúdo/a',
                                                default => 'Desconocido',
                                            }),
                                        TextEntry::make('fecha_de_nacimiento')
                                            ->date('d/m/Y'),
                                        TextEntry::make('domicilio')
                                            ->placeholder('-'),
                                        TextEntry::make('telefono')
                                            ->label('Teléfono')
                                            ->placeholder('-'),
                                        TextEntry::make('telefono_emergencia')
                                            ->label('Teléfono de Emergencia')
                                            ->placeholder('-'),
                                    ]),
                                Section::make('Estudio Alcanzado')
                                    ->relationship('estudioPrioritario')
                                    ->schema([
                                        TextEntry::make('institucion')
                                            ->label('Institución')
                                            ->placeholder('-'),
                                        TextEntry::make('nivel_estudio')
                                            ->label('Nivel de Estudio')
                                            ->placeholder('-')
                                            ->formatStateUsing(fn (int $state): string => match ($state) {
                                                    1 => 'Primaria',
                                                    2 => 'Secundaria',
                                                    3 => 'Terciario',
                                                    4 => 'Universitario',
                                                    5 => 'Doctorado',
                                                    6 => 'Maestría',
                                                    default => 'Desconocido',
                                                }
                                            ),
                                        TextEntry::make('fecha_fin')
                                            ->label('Fecha de Finalización')
                                            ->placeholder('-')
                                            ->date('d/m/Y'),
                                    ])->columns(3),
                            ]),
                        Tabs\Tab::make('Tab 2')
                            ->label('Papeles')
                            ->schema([
                                Section::make('Legajos de la Persona')
                                ->schema([
                                    RepeatableEntry::make('legajos')
                                    ->placeholder('-')
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('num_legajo')
                                            ->label('Número de legajo')
                                            ->placeholder('-'),
                                        TextEntry::make('fecha_de_ingreso')
                                            ->label('Fecha de Ingreso')
                                            ->placeholder('-'),
                                        TextEntry::make('estado')
                                            ->label('Estado')
                                            ->placeholder('-'),
                                    ]),
                                ])
                            ]),
                    ]),
                

                /*Section::make('Estudios de la persona')
                    ->schema([
                        RepeatableEntry::make('estudios')
                        ->placeholder('-')
                        ->columns(3)
                        ->grid(1)
                        ->schema([
                            TextEntry::make('institucion')
                                ->label('Institución')
                                ->placeholder('-'),
                            TextEntry::make('nivel_estudio')
                                ->label('Nivel de Estudio')
                                ->placeholder('-')
                                ->formatStateUsing(fn (int $state): string => match ($state) {
                                        1 => 'Primaria',
                                        2 => 'Secundaria',
                                        3 => 'Terciario',
                                        4 => 'Universitario',
                                        5 => 'Doctorado',
                                        6 => 'Maestría',
                                        default => 'Desconocido',
                                    }
                                ),
                            TextEntry::make('fecha_fin')
                                ->label('Fecha de Finalización')
                                ->placeholder('-')
                                ->date('d/m/Y'),
                        ]),
                    ])*/
                    
                    
                    
            ]);
    }
}
