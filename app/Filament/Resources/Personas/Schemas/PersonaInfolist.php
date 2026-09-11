<?php

namespace App\Filament\Resources\Personas\Schemas;

use App\Filament\Resources\Personas\PersonaResource;
use App\Models\AntecedenteLaboral;
use App\Models\Curso;
use App\Models\Documento;
use App\Models\Familiar;
use App\Models\Idioma;
use App\Models\Legajo;
use App\Models\Persona;
use App\Models\Titulo;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class PersonaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
        ->components([
            Tabs::make('Tabs_base')
            ->columnSpanFull()
            ->tabs([
                Tab::make('Tab Persona')
                ->label('Persona')
                ->id('persona')
                ->icon('heroicon-m-identification')
                ->columns(4)
                ->schema(Persona::getOutSchema('list')),

                Tab::make('Tab Familiares')
                ->label('Familiares')
                ->id('familiar')
                ->icon('heroicon-m-users')
                ->schema([
                    RepeatableEntry::make('familiares')
                    ->hiddenLabel()
                    ->grid(2)
                    ->columns(2)
                    ->placeholder('No se adjuntó ningún Familiar.')
                    ->schema(Familiar::getOutSchema('list')),
                ]),

                Tab::make('Tab Idiomas')
                ->label('Idiomas')
                ->id('idioma')
                ->icon('heroicon-m-language')
                ->schema([
                    Section::make('Idiomas')
                    ->schema([
                        RepeatableEntry::make('idiomas')
                        ->hiddenLabel()
                        ->placeholder('Sin idiomas registrados')
                        ->columns(2)
                        ->schema(Idioma::getOutSchema('list')),
                    ]),
                ]),

                Tab::make('Tab Papeles')
                ->label('Papeles')
                ->icon('heroicon-m-folder-open')
                ->columnSpanFull()
                ->schema([
                    Section::make('Legajos de la Persona')
                    ->columnSpanFull()
                    ->headerActions([
                        EditAction::make('editar')
                        ->label('Añadir legajos')
                        ->icon('heroicon-m-pencil-square')
                        ->color('warning')
                        ->url(fn (Persona $record): string => PersonaResource::getUrl('edit', ['record' => $record]) . '?tab=legajo'),
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
                                ->url(function (Component $component): string {
                                    $persona = $component->getLivewire()->getRecord();

                                    return PersonaResource::getUrl('edit', ['record' => $persona]) . '?tab=legajo';
                                })
                                ->visible(auth()->user()->isAdmin_RRHH()),
                            ]),
                            Grid::make(2)
                            ->schema([
                                Grid::make(3)
                                ->columnSpan(1)
                                ->schema(Legajo::getOutSchema('list')),

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
                                        ->columns(3)
                                        ->schema(Documento::getOutSchema('list')),
                                    ]),
                                ])
                            ]),
                        ]),
                    ])
                ]),
                
                Tab::make('Tab Estudio/Títulos')
                ->label("Estudios/Títulos")
                ->id('estudio')
                ->icon('heroicon-m-academic-cap')
                ->schema([
                    Section::make('Estudio Alcanzado')
                    ->columnSpanFull()
                    ->columns(3)
                    ->relationship('estudioPrioritario')
                    ->headerActions([
                        Action::make('editar')
                        ->label('Añadir Estudio/Título')
                        ->icon('heroicon-m-pencil-square')
                        ->color('warning')
                        ->visible(auth()->user()->isAdmin_RRHH())
                        ->url(function (Component $component): string {
                            // Navega hacia arriba en el árbol de componentes para obtener la Persona real
                            $persona = $component->getLivewire()->getRecord();

                            return PersonaResource::getUrl('edit', ['record' => $persona]) . '?tab=estudio';
                        }),
                    ])
                    ->schema([
                        TextEntry::make('institucion')
                        ->label('Institución')
                        ->placeholder('-'),

                        TextEntry::make('nivel_estudio')
                        ->label('Nivel de Estudio'),

                        TextEntry::make('fecha_fin')
                        ->label('Fecha de Finalización')
                        ->placeholder('-')
                        ->date('d/m/Y'),
                        
                        RepeatableEntry::make('titulos') // Nombre de la relación en tu modelo 'Estudio'
                        ->label('Títulos Obtenidos')
                        ->columnSpanFull() // Ocupa todo el ancho debajo de los campos anteriores
                        ->placeholder('Sin títulos registrados para este estudio')
                        ->grid(2) // Si tiene varios títulos, los muestra en 2 columnas
                        ->schema(Titulo::getOutSchema('list')),

                    ]),

                ]),

                Tab::make('Tab Cursos')
                ->label('Cursos')
                ->id('curso')
                ->icon('heroicon-m-book-open')
                ->columnSpanFull()
                ->schema([
                    Section::make('Cursos y Capacitaciones')
                    ->columnSpanFull()
                    ->headerActions([
                        EditAction::make('editar')
                        ->label('Añadir Cursos')
                        ->icon('heroicon-m-pencil-square')
                        ->color('warning')
                        ->url(fn (Persona $record): string => PersonaResource::getUrl('edit', ['record' => $record]) . '?tab=curso'),
                    ])
                    ->schema([
                        RepeatableEntry::make('cursos')
                        ->hiddenLabel()
                        ->placeholder('Sin cursos registrados')
                        ->columns(3)
                        ->schema(Curso::getOutSchema('list')),
                    ]),
                ]),

                Tab::make('Tab Antecedentes Laborales')
                ->label('Antecedentes Laborales')
                ->id('antecedenteslaborales')
                ->icon('heroicon-m-briefcase')
                ->columnSpanFull()
                ->schema([
                    Section::make('Antecedentes Laborales')
                    ->columnSpanFull()
                    ->headerActions([
                        EditAction::make('editar')
                        ->label('Añadir Antecedentes Laborales')
                        ->icon('heroicon-m-pencil-square')
                        ->color('warning')
                        ->url(fn (Persona $record): string => PersonaResource::getUrl('edit', ['record' => $record]) . '?tab=antecedenteslaborales'),
                    ])
                    ->schema([
                        RepeatableEntry::make('antecedentesLaborales')
                        ->hiddenLabel()
                        ->placeholder('Sin antecedentes registrados')
                        ->columns(3)
                        ->schema(AntecedenteLaboral::getOutSchema('list')),
                    ]),
                ]),

                Tab::make('Tab Usuario')
                ->label('Usuario')
                ->icon('heroicon-m-user-circle')
                ->id('usuario')
                ->columnSpanFull()
                ->visible(auth()->user()->isAdmin_RRHH())
                ->schema([
                    Section::make('Usuario')
                    ->columnSpanFull()
                    ->relationship('Usuario')
                    ->headerActions([
                        EditAction::make('editar')
                        ->label(function ($component): string {
                            $persona = $component->getRecord();
                            return $persona?->Usuario ? 'Editar Usuario Adjunto' : 'Adjuntar Usuario';
                        })
                        ->icon(function ($component): string {
                            $persona = $component->getRecord();
                            return $persona?->Usuario ? 'heroicon-m-pencil-square' : 'heroicon-m-plus';
                        })
                        ->color(function ($component): string {
                            $persona = $component->getRecord();
                            return $persona?->Usuario ? 'warning' : 'primary';
                        })
                        ->url(function($component) : string{
                            $persona = $component->getRecord();
                            return PersonaResource::getUrl('edit', ['record' => $persona->id]) . '?tab=usuario';
                        }),
                    ])
                    ->schema(User::getOutSchema('folist')),
                ]),
            ]),
        ]);
    }
}
