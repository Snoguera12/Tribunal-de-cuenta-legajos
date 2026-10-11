<?php

namespace App\Filament\Resources\Personas\Schemas;

use App\Models\AntecedenteLaboral;
use App\Models\Curso;
use App\Models\Estudio;
use App\Models\EstudioTitulo;
use App\Models\Familiar;
use App\Models\Idioma;
use App\Models\Legajo;
use App\Models\Persona;
use App\Models\Titulo;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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
                ->schema(Familiar::getRepeaterSchema()),

                Tab::make('Tab Idiomas')
                ->label('Idiomas')
                ->id('idioma')
                ->icon('heroicon-m-language')
                ->schema(Idioma::getRepeaterSchema()),
                
                Tab::make('Tab Legajos/Documentos')
                ->icon('heroicon-m-folder-open')
                ->id('legajo')
                ->label('Legajos')
                ->icon('heroicon-m-folder-open')
                ->schema(Legajo::getRepeaterSchema()),

                Tab::make('Tab Estudios/Títulos')
                ->label("Estudios/Títulos")
                ->id('estudio')
                ->icon('heroicon-m-academic-cap')
                ->columnSpanFull()
                ->schema(Estudio::getRepeaterSchema()),

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
                    ->defaultItems(0)
                    ->addActionLabel('Agregar un Curso')
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
                    ->defaultItems(0)
                    ->addActionLabel('Agregar un Antecedente Laboral')
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
                ->visible(fn () => auth()->user()->isAdmin_RRHH())
                ->schema([
                    Toggle::make('crear_usuario')
                    ->label('Crear usuario para esta persona')
                    ->live()
                    ->dehydrated(false)
                    ->visible(fn (?Persona $record) => !$record?->usuario()->exists())
                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                        if (!$state) {
                            return;
                        }

                        if (blank($get('usuario.email'))) {
                            $set('usuario.email', $get('email'));
                        }

                        if (blank($get('usuario.name'))) {
                            $set('usuario.name', trim($get('nombre') . ' ' . $get('apellido')));
                        }
                    }),
                    
                    Group::make()
                    ->columnSpanFull()
                    ->relationship(
                        'usuario',
                        condition: fn (?array $state) => filled($state['email'] ?? null),
                    )
                    ->columns(2)
                    ->visible(fn (Get $get, ?Persona $record) =>
                        $record?->usuario()->exists() || $get('crear_usuario'))
                    ->schema(User::getFormSchema(false)),
                ]),

            ]),
        ]);
    }
}

