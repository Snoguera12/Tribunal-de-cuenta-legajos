<?php

namespace App\Filament\Resources\Personas\Schemas;

use App\Models\AntecedenteLaboral;
use App\Models\Curso;
use App\Models\Estudio;
use App\Models\Familiar;
use App\Models\Idioma;
use App\Models\Legajo;
use App\Models\Persona;
use App\Models\Titulo;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;

class PersonaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
        ->components([
            Tabs::make('Tabs Base')
            ->columns(2)
            ->columnSpanFull()
            ->persistTabInQueryString()
            ->tabs([
                Tab::make('Tab Persona')
                ->label('Persona')
                ->id('persona')
                ->icon('heroicon-m-identification')
                ->columns(4)
                ->columnSpanFull()
                ->schema(Persona::getFormSchema()),

                Tab::make('Tab Familiares')
                ->label('Familiares')
                ->id('familiar')
                ->icon('heroicon-m-users')
                ->schema([
                    Repeater::make('familiares')
                    ->relationship('familiares')
                    ->hiddenLabel()
                    ->columnSpanFull()
                    ->collapsible()
                    ->grid(2)
                    ->itemLabel("Añadir un Familar")
                    ->addActionLabel('Agregar otro Familiar')
                    ->addAction(
                        fn (Action $action) => $action
                        ->icon('heroicon-m-plus')
                        ->color('gray')
                    )
                    ->schema([
                        Grid::make(2)
                        ->columnSpan(1)
                        ->schema(Familiar::getFormSchema())
                    ]),
                ]),

                Tab::make('Tab Idiomas')
                ->label('Idiomas')
                ->id('idioma')
                ->icon('heroicon-m-language')
                ->schema([
                    Repeater::make('idiomas')
                    ->relationship('idiomas')
                    ->hiddenLabel()
                    ->columnSpanFull()
                    ->addActionLabel('Añadir otro Idioma')
                    ->itemLabel('Idioma Adjuntado')
                    ->collapsible()
                    ->grid(3) // Mantiene tus dos columnas de documentos lado a lado
                    ->schema([
                        Grid::make(1)
                        ->columnSpan(1)
                        ->schema(Idioma::getFromSchema()),
                    ]),
                ]),
                
                Tab::make('Tab Legajos/Documentos')
                ->icon('heroicon-m-folder-open')
                ->id('legajo')
                ->label('Legajos') // Etiqueta más descriptiva para el usuario
                ->icon('heroicon-m-folder-open')
                ->schema([
                    Repeater::make('Legajo')
                    ->relationship('legajos')
                    ->hiddenLabel()
                    ->columnSpanFull()
                    ->collapsible() // Permite encoger legajos antiguos para mantener orden visual
                    ->addActionLabel('Añadir un Legajo')
                    ->itemLabel(
                        fn (array $state): ?string => ($state['num_legajo'] ?? null) ? "Legajo N° " . $state['num_legajo']: 'Nuevo Registro de Legajo' 
                    ) // Muestra un título claro en cada bloque (Ej: "Legajo N° 4512")
                    ->addAction(
                        fn (Action $action) => $action
                        ->icon('heroicon-m-plus')
                        ->color('gray')
                    )
                    ->schema(Legajo::getFrontLegajoDocumentos('mul')),
                ]),

                Tab::make('Tab Estudios/Títulos')
                ->label("Estudios/Títulos")
                ->id('estudio')
                ->icon('heroicon-m-academic-cap')
                ->columnSpanFull()
                ->schema([
                    Repeater::make('Estudio')
                    ->relationship('estudios')
                    ->hiddenLabel()
                    ->columnSpanFull()
                    ->addActionLabel('Añadir otro Estudio')
                    ->addAction(
                        fn (Action $action) => $action
                        ->icon('heroicon-m-plus')
                        ->color('gray')
                    )
                    ->collapsible()
                    ->defaultItems(0)
                    ->itemLabel(
                        fn (array $state): ?string => ($state['institucion'] ?? null) ? ($state['institucion']): 'Nuevo Registro de Estudio'
                    )
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'sm' => 3,
                        ])->schema(Estudio::getFormSchema()),

                        Section::make('Títulos Obtenidos')
                        ->compact()
                        ->visible(fn (Get $get) => Estudio::requiereTitulo($get('nivel_estudio')))
                        ->extraAttributes([
                                // Forzamos un borde más oscuro y fondo claro para que resalte del contenedor de Legajos
                                'style' => '
                                    border: 2px solid #b4b4b4 !important; 
                                    border-radius: 12px !important; 
                                    background-color: #f9fafb !important;
                                    box-shadow: 0 2px 4px rgba(0,0,0,0.05) !important;
                                '
                            ])
                        ->schema([
                            Repeater::make('titulos')
                            ->relationship('titulos') 
                            ->addActionLabel('Añadir un Título')
                            ->hiddenLabel()
                            ->itemLabel('Nuevo Registro de Título')
                            ->collapsible()
                            ->grid(3)
                            ->schema(Titulo::getFormSchema(false))
                        ])
                    ]),
                ]),

                Tab::make('Tab Cursos')
                ->label('Cursos')
                ->id('curso')
                ->icon('heroicon-m-book-open')
                ->columnSpanFull()
                ->schema([
                    Repeater::make('Curso')->relationship('cursos')
                    ->hiddenLabel()
                    ->columnSpanFull()
                    ->columns(3)
                    ->addActionLabel('Agregar otro Curso')
                    ->addAction(
                        fn (Action $action) => $action
                        ->icon('heroicon-m-plus')
                        ->color('gray')
                    )
                    ->schema(Curso::getFromSchema())
                ]),

                Tab::make('Tab Antecedentes Laborales')
                ->label('Antecedentes Laborales')
                ->id('antecedenteslaborales')
                ->icon('heroicon-m-briefcase')
                ->columnSpanFull()
                ->schema([
                    Repeater::make('Laboral')->relationship('antecedentesLaborales')
                    ->hiddenLabel()
                    ->columnSpanFull()
                    ->addActionLabel('Añadir antecedente laboral')
                    ->addAction(
                        fn (Action $action) => $action
                        ->icon('heroicon-m-plus')
                        ->color('gray')
                    )
                    ->columns(3)
                    ->schema(AntecedenteLaboral::getFormSchema())
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
                    ->columns(2)
                    ->schema([
                        // Esos tres puntos se llama operador de propagación (spread operator)
                        ...User::getFormSchema(false)
                    ]), 
                ]),

            ]),
        ]);
    }
}

