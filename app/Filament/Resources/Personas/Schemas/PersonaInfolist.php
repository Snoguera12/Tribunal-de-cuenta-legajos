<?php

namespace App\Filament\Resources\Personas\Schemas;

use App\Filament\Actions\MotivoBajaAction;
use App\Filament\Resources\Legajos\Schemas\LegajoForm;
use App\Filament\Resources\Personas\PersonaResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Legajo;
use App\Models\Persona;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
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
                 ->columns(4)
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
                Tab::make('Tab 2_')
                ->label('Familiares')
                ->schema([
                    Section::make('Familiares')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('familiares')
                        ->hiddenLabel()
                        ->grid(2)
                        ->columns(2)
                        ->placeholder('No se adjuntó ningún Familiar.')
                        ->extraAttributes([
                            // Esto busca los elementos li internos con esa clase y les clava el borde negro grueso
                            'style' => '--ring-color: transparent; border: 2px solid black !important; border-radius: 0.75rem;',
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
                Tab::make('Tab 3_')
                ->label('Idiomas')
                ->schema([
                    Section::make('Idiomas')
                    
                    ->schema([
                        RepeatableEntry::make('idiomas')
                        ->hiddenLabel()
                        ->placeholder('Sin idiomas registrados')
                        ->columns(2)
                        ->schema([
                            TextEntry::make('idioma')->label('Idioma'),
                            TextEntry::make('nivel')->label('Nivel'),
                        ]),
                    ]),
                ]),
                Tab::make('Tab 2')
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
                        TextEntry::make('institucion')->label('Institución')->placeholder('-'),
                        TextEntry::make('nivel_estudio')->label('Nivel de Estudio'),
                        TextEntry::make('fecha_fin')->label('Fecha de Finalización')->placeholder('-')
                        ->date('d/m/Y'),
                        RepeatableEntry::make('titulos') // Nombre de la relación en tu modelo 'Estudio'
                        ->label('Títulos Obtenidos')
                        ->columnSpanFull() // Ocupa todo el ancho debajo de los campos anteriores
                        ->placeholder('Sin títulos registrados para este estudio')
                        ->grid(2) // Si tiene varios títulos, los muestra en 2 columnas
                        ->schema([
                            TextEntry::make('nombre') // Campo 'nombre' de tu tabla de títulos
                                ->hiddenLabel() // Oculta la etiqueta repetitiva dentro de la cuadrícula
                                ->icon('heroicon-m-academic-cap') // Un ícono visual para el título
                        ]),

                    ]),
                    
                ]),
                Tab::make('Tab 4')
                ->label('Cursos')
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
                        ->schema([
                            TextEntry::make('nombre')->label('Curso'),
                            TextEntry::make('institucion')->label('Institución')->placeholder('-'),
                            TextEntry::make('duracion')->label('Duración')->placeholder('-'),
                            TextEntry::make('fecha')->label('Fecha')->placeholder('-')
                            ->date('d/m/Y'),
                            TextEntry::make('tiene_certificado')->label('Certificado')
                            ->formatStateUsing(fn ($state) => $state ? 'Sí' : 'No'),
                        ]),
                    ]),
                ]),
                Tab::make('Tab 5')
                ->label('Antecedentes Laborales')
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
                        ->schema([
                            TextEntry::make('empleador')->label('Empleador'),
                            TextEntry::make('lugar_de_trabajo')->label('Lugar de trabajo'),
                            TextEntry::make('cargo')->label('Cargo')->placeholder('-'),
                            TextEntry::make('fecha_inicio')->label('Fecha inicio')->placeholder('-')
                            ->date('d/m/Y'),
                            TextEntry::make('fecha_fin')->label('Fecha fin')->placeholder('-')
                            ->date('d/m/Y'),
                            TextEntry::make('motivo_egreso')->label('Motivo de egreso')->placeholder('-'),
                        ]),
                    ]),
                ]),
                Tab::make('Tab 6')
                ->label('Usuario')
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
                        ->url(function ($component): string {
                            $persona = $component->getRecord();
                            
                            // Si ya tiene usuario, lo mandamos a editar el usuario existente
                            if ($persona?->Usuario) {
                                return UserResource::getUrl('edit', ['record' => $persona->Usuario->id]);
                            }
                            
                            // Si no tiene, lo mandamos a crear uno pasando el ID de la persona como parámetro
                            return UserResource::getUrl('create', ['persona_id' => $persona->id]);
                        }),
                    ])
                    ->schema([
                        TextEntry::make('name')->label('Nombre de Usuario')->placeholder('-'),
                        TextEntry::make('email')->label('Correo Electrónico')->placeholder('-'),
                        TextEntry::make('rol')->label('Rol del Usuario')->placeholder('-')
                        ->formatStateUsing(fn (int $state): string => match ($state) {
                            1 => 'Empleado',
                            2 => 'Funcionario',
                            3 => 'RRHH (Recursos Humanos.)',
                            4 => 'Administrador',
                            default => 'Desconocido',
                        }),
                    ]),
                ]),
            ]),
        ]);
    }
}
