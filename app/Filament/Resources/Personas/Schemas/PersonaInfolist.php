<?php

namespace App\Filament\Resources\Personas\Schemas;

use App\Models\Legajo;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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
                        ->columnSpanFull()
                        ->schema([
                            Section::make('Legajos de la Persona')
                            ->columnSpanFull()
                            ->schema([
                                RepeatableEntry::make('legajos')
                                ->hiddenLabel()
                                ->placeholder('Sin legajo')
                                ->columns(1)
                                ->schema([
                                    Grid::make(2)
                                    ->schema([
                                        Grid::make(3)
                                            ->columnSpan(1)
                                            ->schema([
                                                TextEntry::make('num_legajo')
                                                    ->label('Número de legajo')
                                                    ->placeholder('-'),
                                                TextEntry::make('fecha_de_ingreso')
                                                    ->label('Fecha de Ingreso')
                                                    ->placeholder('-'),
                                                TextEntry::make('estado')
                                                    ->label('Estado')
                                                    ->icon(fn (Legajo $record) => $record->isAlta() ? Heroicon::CheckCircle : Heroicon::XCircle)
                                                    ->color(fn (Legajo $record) => $record->isAlta() ? 'success' : 'danger')
                                                    ->iconColor(fn (Legajo $record) => $record->isAlta() ? 'success' : 'danger')
                                                    ->placeholder('-'),
                                                TextEntry::make('area.nombre')
                                                    ->label('Área')
                                                    ->placeholder('-'),
                                                TextEntry::make('categoria.nombre')
                                                    ->label('Categoría')
                                                    ->placeholder('-'),
                                                TextEntry::make('cargo.nombre')
                                                    ->label('Cargo')
                                                    ->placeholder('-'),
                                            ]),
                                            Section::make('Documentos Adjuntos')
                                                ->columnSpan(1)
                                                ->extraAttributes([
                                                    'style' => 'max-height: 300px; overflow-y: auto;', 
                                                ])
                                                ->schema([
                                                    RepeatableEntry::make('documentos')
                                                        ->label('Archivos Adjuntos')
                                                        ->hiddenLabel()
                                                        ->placeholder('Sin documentos')
                                                        ->contained(false) // Quita el recuadro gris individual por documento
                                                        ->grid(1)
                                                        ->extraAttributes([
                                                            // Clases de Tailwind para aplicar un borde gris sutil, fondo blanco y separación inferior
                                                            'class' => '[&>div]:border [&>div]:border-gray-200 [&>div]:bg-white [&>div]:shadow-sm [&>div]:mb-3 last:[&>div]:mb-0'
                                                        ])
                                                        ->schema([
                                                            Grid::make(2)
                                                                ->columns(2)
                                                                ->schema([
                                                                    TextEntry::make('tipodoc')
                                                                        ->label('Tipo'),
                                                                    
                                                                    TextEntry::make('fecha_de_creacion')
                                                                        ->label('Fecha de Creación')
                                                                        ->dateTime('d/m/Y H:i:m'),
                                                                ]),
                                                            TextEntry::make('archivo')
                                                                ->hiddenLabel()
                                                                ->bulleted()
                                                                ->icon('heroicon-o-document-arrow-down')
                                                                ->color('primary')
                                                                ->url(fn ($record) => $record->archivo ? Storage::url($record->archivo) : null)
                                                                ->openUrlInNewTab(),

                                                        ]),
                                                ])
                                    ]),
                                    
                                    
                                ]),
                            ])
                        ]),
                    ]),
            ]);
    }
}
