<?php

// IMPORTANTE: El namespace debe coincidir exactamente con la estructura de carpetas
namespace App\Filament\Actions;

use App\Enums\TipoContratoEnum;
use App\Enums\TipodocEnum;
use App\Models\Area;
use App\Models\Cargo;
use App\Models\Categoria;
use App\Models\Legajo;
use Carbon\Carbon;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Model;

class LegajoFormPortAction extends Action
{
    /**
     * El nombre por defecto de la acción.
     */
    public static function getDefaultName(): ?string
    {
        return 'crearLegajo';
    }

    /**
     * Configuración inicial de la acción.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Agregar Legajo')
            ->icon('heroicon-o-document-plus')
            ->color('success')
            
            // Tu esquema de pestañas exacto
            ->form([
                Tabs::make('Tabs_Base')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Tab Legajo')
                            ->label('Legajo')
                            ->columns(2)
                            ->schema([
                                TextInput::make('num_legajo')->label('Número de legajo')
                                    ->required()
                                    ->numeric()
                                    ->rules([
                                        'gt:0',
                                        'unique:legajos,num_legajo'
                                    ])
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
                            ->schema([
                                Repeater::make('documentos')
                                    ->hiddenLabel()
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->schema([
                                        Grid::make(1)
                                            ->columns(2)
                                            ->schema([
                                                Textarea::make('descripcion')->label('Descripción')
                                                    ->required()
                                                    ->columnSpanFull(),
                                                Select::make('tipodoc')->label('Tipo de Documento.')
                                                    ->required()
                                                    ->options(TipodocEnum::class),
                                                DateTimePicker::make('fecha_de_creacion')->label('Fecha de Creación.')
                                                    ->displayFormat('d/m/Y H:i:s')
                                                    ->format('Y-m-d H:i:s')
                                                    ->default(Carbon::now())
                                                    ->helperText('Si no introduce la fecha de creación, se asigna la fecha de hoy.'),
                                            ]),
                                        Grid::make(1)
                                            ->schema([
                                                FileUpload::make('archivo')->label('Documento Adjunto')
                                                    ->disk('public')
                                                    ->directory('documentos/')
                                                    ->visibility('public')
                                                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                                                    ->maxSize(10240)
                                                    ->required(),
                                            ]),
                                    ])
                            ]),
                    ]),
            ])
            // Guardado utilizando el $record inyectado dinámicamente por Filament (el modelo Persona actual)
            ->action(function (array $data, Model $record): void {
                $fechaIngreso = $data['fecha_de_ingreso'] ?? Carbon::now()->format('Y-m-d H:i:s');

                $legajo = Legajo::create([
                    'num_legajo'       => $data['num_legajo'],
                    'tipo_contrato'    => $data['tipo_contrato'],
                    'persona_id'       => $record->id, 
                    'area_id'          => $data['area_id'],
                    'cargo_id'         => $data['cargo_id'],
                    'categoria_id'     => $data['categoria_id'],
                    'fecha_de_ingreso' => $fechaIngreso,
                ]);

                if (!empty($data['documentos'])) {
                    foreach ($data['documentos'] as $doc) {
                        $legajo->documentos()->create([
                            'descripcion'       => $doc['descripcion'],
                            'tipodoc'           => $doc['tipodoc'],
                            'fecha_de_creacion' => $doc['fecha_de_creacion'] ?? Carbon::now()->format('Y-m-d H:i:s'),
                            'archivo'           => $doc['archivo'],
                        ]);
                    }
                }
                
                redirect(request()->header('Referer')); 
            })
            ->successNotificationTitle('Legajo y documentos asociados correctamente');
    }
}
