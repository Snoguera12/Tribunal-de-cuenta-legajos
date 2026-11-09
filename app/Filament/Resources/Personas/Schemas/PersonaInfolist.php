<?php

namespace App\Filament\Resources\Personas\Schemas;

use App\Models\AntecedenteLaboral;
use App\Models\Curso;
use App\Filament\Resources\Legajos\Pages\ViewLegajo;
use App\Filament\Resources\Legajos\RelationManagers\DocumentosRelationManager;
use App\Filament\Resources\Legajos\RelationManagers\LicenciasRelationManager;
use Filament\Schemas\Components\Livewire;
use App\Models\Estudio;
use App\Models\Familiar;
use App\Models\PersonaIdioma;
use App\Models\Legajo;
use App\Models\Persona;
use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Infolists\Components\RepeatableEntry;
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
            ->persistTabInQueryString()
            ->tabs([
                Tab::make('Tab Persona')
                ->label('Persona')
                ->id('persona')
                ->key('persona')
                ->icon('heroicon-m-identification')
                ->columns(4)
                ->schema(Persona::getOutSchema('list')),

                Tab::make('Tab Legajos')
                ->label('Legajos')
                ->id('legajo')
                ->key('legajo')
                ->icon('heroicon-m-folder-open')
                ->columnSpanFull()
                ->schema([
                    RepeatableEntry::make('legajos')
                    ->hiddenLabel()
                    ->placeholder('Sin legajo')
                    ->contained(false)
                    ->columns(1)
                    ->schema([
                        Section::make()
                        ->heading(fn ($record) => 'Legajo N° ' . $record->num_legajo)
                        ->collapsible() // botón de mostrar/ocultar por legajo
                        ->compact()
                        ->schema([
                        Tabs::make('Tabs_legajo')
                        ->columnSpanFull()
                        ->contained(false)
                        ->tabs([
                            Tab::make('Tab Datos del Legajo')
                                ->label('Legajo')
                                ->id('legajo-datos')
                                ->icon('heroicon-m-folder-open')
                                ->schema([
                                    Grid::make(3)->schema(Legajo::getOutSchema('list')),
                                ]),

                            Tab::make('Tab Documentos del Legajo')
                                ->label('Documentos')
                                ->id('legajo-documentos')
                                ->icon('heroicon-m-document-text')
                                ->schema([
                                    Livewire::make(DocumentosRelationManager::class, fn ($record) => [
                                        'ownerRecord' => $record,
                                        'pageClass' => ViewLegajo::class,
                                    ])->key(fn ($record) => 'documentos-rm-' . $record->getKey()),
                                ]),

                            Tab::make('Tab Licencias del Legajo')
                                ->label('Licencias')
                                ->id('legajo-licencias')
                                ->icon('heroicon-m-clipboard-document-check')
                                ->schema([
                                    Livewire::make(LicenciasRelationManager::class, fn ($record) => [
                                        'ownerRecord' => $record,
                                        'pageClass' => ViewLegajo::class,
                                    ])->key(fn ($record) => 'licencias-rm-' . $record->getKey()),
                                ]),
                            ]),
                        ])
                    
                    ]),
                ]),
                
                Tab::make('Tab Estudio/Títulos')
                ->label('Estudios/Títulos')
                ->id('estudio')
                ->key('estudio')
                ->icon('heroicon-m-academic-cap')
                ->schema([
                    RepeatableEntry::make('estudiosPrincipal')
                    ->hiddenLabel()
                    ->placeholder('Sin estudios terminados registrados')
                    ->contained(false)
                    ->columns(3)
                    ->schema(Estudio::getOutSchema('list')),

                    Section::make()
                    ->heading(fn ($record) => 'Mostrar más estudios (' . $record->estudiosAnteriores()->count() . ')')
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn ($record) => $record->estudiosAnteriores()->exists())
                    ->schema([
                        RepeatableEntry::make('estudiosAnteriores')
                        ->hiddenLabel()
                        ->contained(false)
                        ->columns(3)
                        ->schema(Estudio::getOutSchema('list')),
                    ]),
                ]),

                Tab::make('Tab Cursos')
                ->label('Cursos')
                ->id('curso')
                ->key('curso')
                ->icon('heroicon-m-book-open')
                ->columnSpanFull()
                ->schema([
                    RepeatableEntry::make('cursos')
                    ->hiddenLabel()
                    ->placeholder('Sin cursos registrados')
                    ->columns(3)
                    ->schema(Curso::getOutSchema('list')),
                ]),

                Tab::make('Tab Antecedentes Laborales')
                ->label('Antecedentes Laborales')
                ->id('antecedenteslaborales')
                ->key('antecedenteslaborales')
                ->icon('heroicon-m-briefcase')
                ->columnSpanFull()
                ->schema([
                    RepeatableEntry::make('antecedentesLaborales')
                    ->hiddenLabel()
                    ->placeholder('Sin antecedentes registrados')
                    ->columns(3)
                    ->schema(AntecedenteLaboral::getOutSchema('list')),
                ]),
                
                Tab::make('Tab Familiares')
                ->label('Familiares')
                ->id('familiar')
                ->key('familiar')
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
                ->key('idioma')
                ->icon('heroicon-m-language')
                ->columns(4)
                ->schema([
                    RepeatableEntry::make('idiomas')
                    ->hiddenLabel()
                    ->placeholder('Sin idiomas registrados')
                    ->columns(2)
                    ->schema(PersonaIdioma::getOutSchema('list')),
                ]),

                Tab::make('Tab Usuario')
                ->label('Usuario')
                ->icon('heroicon-m-user-circle')
                ->id('usuario')
                ->key('usuario')
                ->columnSpanFull()
                ->visible(auth()->user()->isAdmin_RRHH())
                ->schema([
                    Group::make()
                    ->relationship('usuario')
                    ->columnSpanFull()
                    ->columns(3)
                    ->visible(fn ($record) => $record->usuario !== null)
                    ->schema(User::getOutSchema('folist')),

                    TextEntry::make('aviso_sin_usuario')
                    ->hiddenLabel()
                    ->state('Esta Persona no está adjuntada a un usuario.')
                    ->color('gray')
                    ->visible(fn ($record) => $record->usuario === null),
                ]),
            ]),
        ]);
    }
}
