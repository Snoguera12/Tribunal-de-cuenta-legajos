<?php

namespace App\Filament\Resources\Legajos\Schemas;

use App\Models\Area;
use App\Models\Cargo;
use App\Models\Categoria;
use App\Models\Persona;
use App\Models\Titulo;
use Carbon\Carbon;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;

class LegajoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tabs')
                    ->tabs([
                        Tabs\Tab::make('Tab 1')
                            ->label('Legajo')
                            ->schema([
                                TextInput::make('num_legajo')
                                    ->label('Número de legajo')
                                    ->required()
                                    ->numeric()
                                    ->unique(table: 'legajos', column: 'num_legajo')
                                    ->rules(['gt:0'])
                                    ->minLength(1)
                                    ->validationMessages([
                                        'required' => 'Requiere introducir el Número de legajo.',
                                        'unique' => 'Este Número de legajo, ya está en uso.',
                                        'gt' => 'El campo :attribute debe ser mayor a cero.',
                                    ])
                                    ->extraInputAttributes([
                                        'oninvalid' => "this.setCustomValidity('Requiere introducir el Número de legajo.')",
                                        'oninput' => "this.setCustomValidity('')",
                                    ]),
                                Select::make('persona_id')
                                    ->label('Persona')
                                    ->required()
                                    ->searchable()
                                    ->options(Persona::selectRaw("id, nombre || ' ' || apellido || ' (DNI: ' || dni || ')' AS nombre_completo")->pluck('nombre_completo', 'id'))
                                    ->validationMessages([
                                        "required" => "Requiere asociar una Persona.",
                                    ])
                                    ->extraInputAttributes([
                                        'oninvalid' => "this.setCustomValidity('Requiere asociar a una Persona.')",
                                        'oninput' => "this.setCustomValidity('')",
                                    ]),
                                Select::make("area_id")
                                    ->label("Nombre del Área")
                                    ->searchable()
                                    ->required()
                                    ->options(Area::all()->pluck("nombre", "id"))
                                    ->validationMessages([
                                        "required" => "Requiere asociar a una Área.",
                                    ])->extraInputAttributes([
                                        'oninvalid' => "this.setCustomValidity('Requiere asociar a una Área.')",
                                        'oninput' => "this.setCustomValidity('')",
                                    ]),
                                Select::make("cargo_id")
                                    ->label("Cargo")
                                    ->searchable()
                                    ->required()
                                    ->options(Cargo::all()->pluck("nombre", "id"))
                                    ->validationMessages([
                                        "required" => "Requiere asociar un cargo.",
                                    ])->extraInputAttributes([
                                        'oninvalid' => "this.setCustomValidity('Requiere asociar a un Cargo.')",
                                        'oninput' => "this.setCustomValidity('')",
                                    ]),
                                Select::make("categoria_id")
                                    ->label("Categoría")
                                    ->searchable()
                                    ->required()
                                    ->options(Categoria::selectRaw("id, nombre || ' ' || descripcion AS nombre_completo")->pluck('nombre_completo', 'id')),
                                Select::make("titulo_id")
                                    ->label("Título")
                                    ->searchable()
                                    ->required()
                                    ->options(Titulo::selectRaw("id, nombre")->pluck('nombre', 'id'))
                                    /*->options([
                                        1 => '12 Ordenanza, Chofer.',
                                        2 => '11 Mayordomo.',
                                        3 => '10 Auxiliar Administrativos C, Revisor C.',
                                        4 => '9 Auxiliar Administrativos B, Revisor B.',
                                        5 => '8 Auxiliar Administrativos A, Revisor A.',
                                        6 => '7 Jefe de sección.',
                                        7 => '6 Opoerador.',
                                        8 => '5 Jefe de despacho y Auditor junior.',
                                        9 => '4 Auditor Semijunior y Sin denominación.',
                                        10 => '3 Audotor Senior y Sin denominación.',
                                        11 => '2 Jefe de rendición.',
                                        12 => '1 Secretario General, Contador/Fiscal.',
                                    ])*/
                                    //->options(Categoria::all()->pluck("nombre", "id"))
                                    ->validationMessages([
                                        "required" => "Requiere asociar a una categoría.",
                                    ])->extraInputAttributes([
                                        'oninvalid' => "this.setCustomValidity('Requiere asociar a una categoría.')",
                                        'oninput' => "this.setCustomValidity('')",
                                    ]),
                                /*
                                TextInput::make('categoria')
                                    ->required()
                                    ->maxLength(3)
                                    ->validationMessages([
                                        "required" => "Requiere introducir la categoría.",
                                    ])->extraInputAttributes([
                                        'oninvalid' => "this.setCustomValidity('Requiere introducir a una Categoría.')",
                                        'oninput' => "this.setCustomValidity('')",
                                    ]),
                                */
                                DateTimePicker::make('fecha_de_ingreso')
                                    ->validationMessages([
                                        "required" => "Requiere introducir la Fecha de ingreso.",
                                    ])
                                    ->native(false)
                                    ->helperText('Si no introduce la fecha de ingreso, se asigna la fecha de hoy.'),
                            ]),
                        Tabs\Tab::make('Tab 2')
                            ->label('Documentos')
                            ->schema([
                                Repeater::make('documento')
                                ->relationship('documentos')
                                ->schema([
                                    FileUpload::make('archivo')
                                        ->label('Documento Adjunto')
                                        ->disk('public') // Disco de almacenamiento (config/filesystems.php)
                                        ->directory('documentos/') // Carpeta destino dentro del disco
                                        ->visibility('public') // Visibilidad del archivo
                                        ->acceptedFileTypes(['application/pdf', 'image/*']) // Restringir formatos
                                        ->maxSize(10240) // Tamaño máximo en KB (10 MB)
                                        ->required(),
                                    Textarea::make('descripcion')
                                        ->label('Descripción')
                                        ->required()
                                        ->columnSpanFull(),
                                    Select::make('tipodoc')
                                        ->label('Tipo de Documento.')
                                        ->required()
                                        ->options([
                                            0 => 'DNI',
                                            1 => 'Título',
                                            2 => 'Cursos',
                                            3 => 'Liciancia',
                                            4 => 'Acta de Nacimiento',
                                            5 => 'Certificado de Escolaridad',
                                            6 => 'Certificado Defunción',
                                            7 => 'Certificado de Casamiento',
                                            8 => 'Sumario',
                                            9 => 'Resolución',
                                            10 => 'Foto de Perfil',
                                            11 => 'Curriculum',
                                            12 => 'Otro',
                                        ]),
                                    Toggle::make('activo'),
                                    DateTimePicker::make('fecha_de_creacion')
                                        ->label('Fecha de Creación.')
                                        ->native(false)
                                        ->displayFormat('d/m/Y H:i:s') // Formato visual para el usuario en la interfaz
                                        ->format('Y-m-d H:i:s')        // Asegura el formato correcto para la base de datos MySQL/PostgreSQL
                                        ->dehydrateStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('Y-m-d H:i:s') : Carbon::now()->format('Y-m-d H:i:s'))
                                        ->helperText('Si no introduce la fecha de creación, se asigna la fecha de hoy.'),
                                ])
                            ])
                    ])->columnSpanFull()
            ]);
    }
}
