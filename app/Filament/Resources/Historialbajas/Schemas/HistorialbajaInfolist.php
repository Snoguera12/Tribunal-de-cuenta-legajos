<?php

namespace App\Filament\Resources\Personas\Schemas;

use App\Enums\EstadoLegajoEnum;
use App\Models\Legajo;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class HistorialbajaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tabs')
                    ->columns(2)
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Tab 1')
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
                                            ->placeholder('-'),
                                        TextEntry::make('estado_civil')
                                            ->label('Estado Civil')
                                            ->placeholder('-'),
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
                        Tab::make('Tab 2')
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
                                            ->icon(fn (Legajo $legajo) => $legajo->isAlta() ? Heroicon::CheckCircle : Heroicon::XCircle)
                                            ->color(fn (Legajo $legajo) => $legajo->isAlta() ? 'success' : 'danger')
                                            ->iconColor(fn (Legajo $legajo) => $legajo->isAlta() ? 'success' : 'danger')
                                            ->placeholder('-')
                                            
                                    ]),
                                ])
                            ]),
                    ]),    
            ]);
    }
}
