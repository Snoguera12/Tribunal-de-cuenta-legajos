<?php

namespace App\Filament\Resources\Personas\Schemas;

use App\Filament\Actions\MotivoBajaAction;
use App\Filament\Resources\Legajos\LegajoResource;
use App\Models\Legajo;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;

class PersonaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
            Tabs::make('Tabs_base')
            ->columns(2)
            ->columnSpanFull()
            ->tabs([
                Tab::make('Tab 1')
                ->label('Persona')
                ->schema([
                    Section::make('Datos Personales')
                    ->columns(4)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('nombre')->label('Nombre'),
                        TextEntry::make('apellido')->label('Apellido'),
                        TextEntry::make('dni')->label('DNI'),
                        TextEntry::make('cuil')->label('CUIL'),
                        TextEntry::make('email')
                        ->label('Correo Electrónico')->placeholder('-'),
                        TextEntry::make('genero')->label('Género'),
                        TextEntry::make('estado_civil')->label('Estado Civil'),
                        TextEntry::make('fecha_de_nacimiento')->date('d/m/Y'),
                        TextEntry::make('domicilio')->placeholder('-'),
                        TextEntry::make('telefono')
                        ->label('Teléfono')->placeholder('-'),
                        TextEntry::make('telefono_emergencia')
                        ->label('Teléfono de Emergencia')->placeholder('-'),
                    ]),
                    Section::make('Familiares')
                    ->schema([
                        RepeatableEntry::make('familiares')
                        ->hiddenLabel()
                        ->columns(2)
                        ->placeholder('No se adjuntó ningún Familiar.')
                        ->extraAttributes([
                            'style' => 'max-height: 552px; overflow-y: auto;',
                        ])
                        ->schema([
                            TextEntry::make('nombre')->label('Nombre'),
                            TextEntry::make('apellido')->label('Apellido'),
                            TextEntry::make('dni')->label('DNI'),
                            TextEntry::make('fecha_de_nacimiento')->label('Fecha de nacimiento')->date('d/m/Y'),
                            TextEntry::make('parentesco')->label('Parentesco'),
                            TextEntry::make('vive')->label('Estado Vital'),
                        ]),
                    ]),
                ]),
                Tab::make('Tab 2')
                ->label('Papeles')
                ->columnSpanFull()
                ->schema([
                    Section::make('Legajos de la Persona')
                    ->columnSpanFull()
                    ->headerActions([
                        EditAction::make('editar')
                        ->label('Añadir legajos')
                        ->url(fn (): string => LegajoResource::getUrl('create')),
                    ])
                    ->schema([
                        RepeatableEntry::make('legajos')
                        ->hiddenLabel()
                        ->placeholder('Sin legajo')
                        ->columns(1)
                        ->schema([
                            Grid::make(2)
                            ->schema([
                                Action::make('editar_legajo')
                                ->label('Editar')
                                ->icon('heroicon-m-pencil-square')
                                ->color('warning')
                                // Accedemos al ID del legajo actual usando el $record
                                ->url(fn (Legajo $record): string => LegajoResource::getUrl('edit', ['record' => $record])),
                                MotivoBajaAction::make(),
                            ]),
                            Grid::make(2)
                            ->schema([
                                Grid::make(3)
                                ->columnSpan(1)
                                ->schema([
                                    TextEntry::make('num_legajo')->label('Número de legajo'),
                                    TextEntry::make('fecha_de_ingreso')->label('Fecha de Ingreso'),

                                    TextEntry::make('estado')
                                    ->label('Estado')
                                    ->icon(fn (Legajo $record) => $record->isAlta() ? Heroicon::CheckCircle : Heroicon::XCircle)
                                    ->color(fn (Legajo $record) => $record->isAlta() ? 'success' : 'danger')
                                    ->iconColor(fn (Legajo $record) => $record->isAlta() ? 'success' : 'danger')
                                    ->placeholder('-'),
                                    
                                    TextEntry::make('tipo_contrato')->label('Tipo de Contratación'),

                                    TextEntry::make('area.nombre')->label('Área'),
                                    TextEntry::make('categoria.nombre')->label('Categoría'),
                                    TextEntry::make('cargo.nombre')->label('Cargo'),
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
                                            TextEntry::make('tipodoc')->label('Tipo'),
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
                Tab::make('Tab 3')
                ->label('Estudio/Título')
                ->schema([
                    Section::make('Estudio Alcanzado')
                    ->columnSpanFull()
                    ->columns(3)
                    ->relationship('estudioPrioritario')
                    ->schema([
                        TextEntry::make('institucion')
                        ->label('Institución')
                        ->placeholder('-'),
                        TextEntry::make('nivel_estudio')->label('Nivel de Estudio'),
                        TextEntry::make('fecha_fin')
                        ->label('Fecha de Finalización')
                        ->placeholder('-')
                        ->date('d/m/Y'),

                    ]),
                ]),
            ]),
        ]);
    }
}
