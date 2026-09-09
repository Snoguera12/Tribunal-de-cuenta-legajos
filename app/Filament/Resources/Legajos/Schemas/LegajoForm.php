<?php

namespace App\Filament\Resources\Legajos\Schemas;

use App\Enums\TipoContratoEnum;
use App\Enums\TipodocEnum;
use App\Models\Area;
use App\Models\Cargo;
use App\Models\Categoria;
use App\Models\Documento;
use App\Models\Legajo;
use App\Models\Persona;
use Carbon\Carbon;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class LegajoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
        ->components([
            Section::make()
            ->columns(3)
            ->columnSpanFull()
            ->schema(Legajo::getFormSchema(true)),

            Section::make('Documentación Digitalizada')
            ->description('Cargue los archivos adjuntos y documentos que respaldan este legajo.')
            ->icon('heroicon-o-document-arrow-up')
            ->collapsible() // El usuario puede ocultar la zona de archivos si no la necesita en el momento
            ->columnSpanFull()
            ->extraAttributes([
                // Forzamos un borde más oscuro y fondo claro para que resalte del contenedor de Legajos
                'style' => '
                    border: 2px solid #2e3032 !important; 
                    border-radius: 12px !important; 
                    background-color: #f9fafb !important;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.05) !important;
                '
            ])
            ->schema([
                Repeater::make('Documento')
                ->relationship('Documentos')
                ->hiddenLabel()
                ->columnSpanFull()
                ->addActionLabel('Adjuntar un Documento')
                ->grid(3)
                ->schema([
                    Section::make()
                    ->columns(1)
                    ->schema([
                        Grid::make(1)
                        ->columnSpan(1)
                        ->schema(Documento::getFromSchema(false, false)),
                    ]),
                ]),
            ]),
        ]);
    }
}

/*
Tabs::make('Tabs_Base')
            ->columnSpanFull()
            ->tabs([
                Tab::make('Tab Legajo')
                ->label('Legajo')
                ->columns(2)
                ->schema([
<<<<<<< HEAD
                    
                
                Section::make('Documentación Digitalizada')
                ->description('Cargue los archivos adjuntos y documentos que respaldan este legajo.')
                ->icon('heroicon-o-document-arrow-up')
                ->collapsible() // El usuario puede ocultar la zona de archivos si no la necesita en el momento
                ->columnSpanFull()
                ->extraAttributes([
                    // Forzamos un borde más oscuro y fondo claro para que resalte del contenedor de Legajos
                    'style' => '
                        border: 2px solid #2e3032 !important; 
                        border-radius: 12px !important; 
                        background-color: #f9fafb !important;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.05) !important;
                    '
                ])
=======
                    TextInput::make('num_legajo')->label('Número de legajo')
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

                    Select::make('tipo_contrato')
                    ->label('Tipo de Contratación.')
                    ->required()
                    ->options(TipoContratoEnum::class),

                         Select::make('persona_id')->label('Persona')
                         ->required()
                         ->searchable()
                         ->options(Persona::Opciones())
                         ->default(fn () => request()->query('persona_id'))
                         ->disabled(fn () => request()->has('persona_id')) // Opcional: deshabilita el campo si ya viene en la URL
                         ->dehydrated() // Obligatorio si usas disabled(), para que guarde el valor en la base de datos
                         ->validationMessages([
                             "required" => "Requiere asociar una Persona.",
                         ])
                         ->extraInputAttributes([
                             'oninvalid' => "this.setCustomValidity('Requiere asociar a una Persona.')",
                                                'oninput' => "this.setCustomValidity('')",
                         ]),

                         Select::make("area_id")->label("Nombre del Área")
                         ->searchable()
                         ->required()
                         ->options(Area::all()->pluck("nombre", "id"))
                         ->validationMessages([
                             "required" => "Requiere asociar a una Área.",
                         ])->extraInputAttributes([
                             'oninvalid' => "this.setCustomValidity('Requiere asociar a una Área.')",
                                                  'oninput' => "this.setCustomValidity('')",
                         ]),

                         Select::make("cargo_id")->label("Cargo")
                         ->searchable()
                         ->required()
                         ->options(Cargo::all()->pluck("nombre", "id"))
                         ->validationMessages([
                             "required" => "Requiere asociar un cargo.",
                         ])->extraInputAttributes([
                             'oninvalid' => "this.setCustomValidity('Requiere asociar a un Cargo.')",
                                                  'oninput' => "this.setCustomValidity('')",
                         ]),

                         Select::make("categoria_id")->label("Categoría")
                         ->searchable()
                         ->required()
                         ->options(Categoria::selectRaw("id, nombre || ' ' || descripcion AS nombre_completo")->pluck('nombre_completo', 'id')),

                         DateTimePicker::make('fecha_de_ingreso')->label('Fecha de Ingreso')
                         ->helperText('Si no introduce la fecha de ingreso, se asigna la fecha de hoy.')
                         ->validationMessages([
                             "required" => "Requiere introducir la Fecha de ingreso.",
                         ]),
                ]),
                Tab::make('Tab Documentos')->label('Documentos')
                ->columns(2)
>>>>>>> origin/emanuel
                ->schema([
                    Repeater::make('Documento')
                    ->relationship('Documentos')
                    ->hiddenLabel()
                    ->columnSpanFull()
                    ->addActionLabel('Adjuntar un Documento')
                    ->grid(2) // Mantiene tus dos columnas de documentos lado a lado
                    ->schema([
                        // SOLUCIÓN NATIVA: Usamos un Fieldset o una Section interna. 
                        // Cada vez que se crea un documento, Filament genera este recuadro contenedor.
                        Section::make()
                        ->columns(1) // Distribuye el contenido internamente en 2 columnas
                        ->schema([
<<<<<<< HEAD
                            // Columna Izquierda: Metadatos del documento
                            Grid::make(1)
                            ->columnSpan(1)
                            ->schema(Documento::getFromSchema(false, false)),
                        ]),
                    ]),
=======
                            Textarea::make('descripcion')->label('Descripción')
                            ->required()
                            ->columnSpanFull(),
                                 Select::make('tipodoc')->label('Tipo de Documento.')
                                 ->required()
                                 ->options(TipodocEnum::class),
                                 DateTimePicker::make('fecha_de_creacion')->label('Fecha de Creación.')
                                 ->displayFormat('d/m/Y H:i:s') // Formato visual para el usuario en la interfaz
                                 ->format('Y-m-d H:i:s')        // Asegura el formato correcto para la base de datos MySQL/PostgreSQL
                                 ->dehydrateStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('Y-m-d H:i:s') : Carbon::now()->format('Y-m-d H:i:s'))
                                 ->helperText('Si no introduce la fecha de creación, se asigna la fecha de hoy.'),
                        ]),
                        Grid::make(1)
                        ->schema([
                            FileUpload::make('archivo')->label('Documento Adjunto')
                            ->disk('documentos_privado')
                            ->directory('documentos')
                            ->visibility('private')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg'])
                            ->rules(['mimes:pdf,jpg,jpeg'])
                            ->maxSize(10240)
                            ->required(),
                        ]),

                    ])
>>>>>>> origin/emanuel
                ]),

            ]),
*/