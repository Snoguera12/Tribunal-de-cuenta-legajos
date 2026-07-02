<?php

namespace App\Filament\Resources\Personas\Schemas;

use App\Enums\EstadoCivilEnum;
use App\Enums\FamiliarViveEnum;
use App\Enums\GeneroEnum;
use App\Enums\IdiomaNivelEnum;
use App\Enums\NivelEstudioEnum;
use App\Enums\ParentescoEnum;
use App\Enums\TipoContratoEnum;
use App\Enums\TipodocEnum;
use App\Models\Area;
use App\Models\Cargo;
use App\Models\Categoria;
use App\Models\Persona;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Hash;

class PersonaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
            Tabs::make('Tabs_base')
            ->columns(2)
            ->columnSpanFull()
            ->persistTabInQueryString()
            ->tabs([
                Tab::make('Tab 1')->label('Persona')
                ->id('persona')
                ->schema([
                    Section::make('Datos Personales')
                    ->columns(4)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('nombre')->label('Nombre')
                        ->required()
                        ->validationMessages([
                            "required" => "Requiere introducir el Nombre.",
                        ])
                        ->extraInputAttributes([
                            'oninvalid' => "this.setCustomValidity('Por favor, introducir el Nombre.')",
                            'oninput' => "this.setCustomValidity('')",
                        ]),
                        TextInput::make('apellido')
                        ->label('Apellido')
                        ->required()
                        ->validationMessages([
                            "required" => "Requiere introducir el Apellido.",
                        ])
                        ->extraInputAttributes([
                            'oninvalid' => "this.setCustomValidity('Por favor, introducir el Apellido.')",
                            'oninput' => "this.setCustomValidity('')",
                        ]),
                        TextInput::make('dni')->label("DNI")
                        ->unique(ignoreRecord: true) // Evita errores al editar el mismo registro
                        ->required()
                        ->maxLength(8)
                        ->live(onBlur: true) 
                        ->rules(['required', 'regex:/^[0-9]{7,8}$/'])
                        ->validationMessages([
                            "required" => "Requiere introducir el DNI.",
                            "unique" => "Ya se registró el DNI.",
                            "regex" => "El DNI debe contener entre 7 y 8 dígitos.",
                        ])
                        ->extraInputAttributes([
                            'type' => 'text',
                            'inputmode' => 'numeric',
                            'oninvalid' => "this.setCustomValidity('Por favor, introducir el DNI.')",
                            'oninput' => "this.setCustomValidity('')",
                        ])
                        ->dehydrateStateUsing(fn (string|null $state) => $state ? (int) preg_replace('/\D/', '', $state) : null)
                        // Se ejecuta al perder el foco si el CUIL está vacío
                        ->afterStateUpdated(function (string|null $state, Set $set, Get $get) {
                            if (blank($state) || filled($get('cuil'))) {
                                return;
                            }

                            // El DNI físico se rellena con ceros a la izquierda hasta tener 8 dígitos para armar el CUIL estándar
                            $dniPad = str_pad(preg_replace('/\D/', '', $state), 8, '0', STR_PAD_LEFT);
                            
                            // Prefijo genérico 20 y sufijo 2 (El usuario podrá corregirlo si es mujer/empresa)
                            $prefijo = '20';
                            $sufijo = '2';

                            $set('cuil', $prefijo . $dniPad . $sufijo);
                        }),

                        TextInput::make('cuil')->label("CUIL")
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(11)
                        // Pasamos un Closure a rules() para que Filament nos inyecte la instancia de Get correctamente
                        ->rules(fn (Get $get): array => [
                            'regex:/^\d{11}$/', // Formato numérico puro de 11 dígitos
                            function (string $attribute, $value, $fail) use ($get) {
                                $dni = preg_replace('/\D/', '', (string) $get('dni'));
                                $cuilNumericoPuro = preg_replace('/\D/', '', (string) $value);

                                if (blank($dni) || strlen($cuilNumericoPuro) !== 11) {
                                    return;
                                }

                                // El DNI dentro del CUIL siempre ocupa 8 dígitos (de la posición 2 a la 9)
                                $dniEnCuil = substr($cuilNumericoPuro, 2, 8);
                                $dniConCeros = str_pad($dni, 8, '0', STR_PAD_LEFT);

                                if ($dniEnCuil !== $dniConCeros) {
                                    $fail("El número de documento intermedio ({$dniEnCuil}) no coincide con el DNI ingresado ({$dni}).");
                                }
                            },
                        ])
                        ->validationMessages([
                            "required" => "Requiere introducir el CUIL.",
                            "unique" => "Ya se registró el CUIL.",
                            "regex" => "El CUIL debe contener exactamente 11 números sin guiones.",
                        ])
                        ->extraInputAttributes([
                            'type' => 'text',
                            'inputmode' => 'numeric',
                        ])
                        ->dehydrateStateUsing(fn (string|null $state) => $state ? (int) preg_replace('/\D/', '', $state) : null),
                        TextInput::make('email')->label('Correo Electróico')
                        ->email()
                        ->extraInputAttributes([
                            'oninvalid' => "this.setCustomValidity('Por favor, escribir correctamente el correo electrónico.')",
                            'oninput' => "this.setCustomValidity('')",
                        ]),
                        Select::make('estado_civil')->label('Estado Civil')
                        ->options(EstadoCivilEnum::class)
                        ->required()
                        ->validationMessages([
                            "required" => "Requiere selecionar el estado civil.",
                        ])
                        ->extraInputAttributes([
                            'oninvalid' => "this.setCustomValidity('Por favor, selecione el género.')",
                            'oninput' => "this.setCustomValidity('')",
                        ]),
                        Select::make("genero")->label("Género")
                        ->options(GeneroEnum::class)
                        ->required()
                        ->validationMessages([
                            "required" => "Requiere selecionar el género.",
                        ])
                        ->extraInputAttributes([
                            'oninvalid' => "this.setCustomValidity('Por favor, selecione el género.')",
                            'oninput' => "this.setCustoEstadoCivilEnum::classmValidity('')",
                        ]),
                        DatePicker::make('fecha_de_nacimiento')->label('Fecha de Nacimiento')
                        ->maxDate(now()->subYears(18)->toDateString()) // Máximo hace 18 años
                        ->rules(['date', 'before_or_equal:' . now()->subYears(18)->toDateString()])
                        ->helperText('La persona tiene que ser mayor de edad.')
                        ->required()
                        ->validationMessages([
                            "required" => "Requiere introducir la Fecha de nacimiento.",
                        ])
                        ->extraInputAttributes([
                            'oninvalid' => "this.setCustomValidity('Por favor, ingrese la fecha de nacimiento.')",
                            'oninput' => "this.setCustomValidity('')",
                        ]),
                        TextInput::make('domicilio'),
                        TextInput::make('telefono')->label('Teléfono')->tel(),
                        TextInput::make('telefono_emergencia')->label('Teléfono de emergencia')->tel(),
                    ]),
                    Section::make('Familiares')
                    ->schema([
                        Repeater::make('Familiar')->relationship('familiares')
                        ->columns(2)
                        ->hiddenLabel()
                        ->addActionLabel('Añadir un Familiar')
                        ->extraAttributes([
                            'style' => 'max-height: 552px; overflow-y: auto;', 
                        ])
                        ->schema([
                            TextInput::make('nombre')->label('Nombre')
                            ->required()
                            ->validationMessages([
                                "required" => "Requiere introducir el Nombre del Familiar.",
                            ]),
                            TextInput::make('apellido')->label('Apellido')
                            ->required()
                            ->validationMessages([
                                "required" => "Requiere introducir el Apellido del Familiar.",
                            ]),
                            TextInput::make('dni')->label('DNI')
                            ->required()
                            ->validationMessages([
                                "required" => "Requiere introducir el DNI del Familiar.",
                            ]),
                            DatePicker::make('fecha_de_nacimiento')->label('Fecha de Nacimiento')
                            ->required()
                            ->validationMessages([
                                "required" => "Requiere introducir la Fecha de nacimiento.",
                            ]),
                            Select::make("parentesco")->label("Parentesco")
                            ->options(ParentescoEnum::class)
                            ->required()
                            ->validationMessages([
                                "required" => "Requiere selecionar el Parentesco del Familiar.",
                            ]),
                            Select::make("vive")->label("Estado Vital")
                            ->options(FamiliarViveEnum::class)
                            ->required()
                            ->validationMessages([
                                "required" => "Requiere selecionar el Estado Vital del Familiar.",
                            ]),
                        ])
                    ]),
                    Section::make('Idiomas')
                    ->schema([
                        Repeater::make('Idioma')->relationship('idiomas')
                        ->columns(2)
                        ->hiddenLabel()
                        ->addActionLabel('Añadir un Idioma')
                        ->extraAttributes([
                            'style' => 'max-height: 552px; overflow-y: auto;', 
                        ])
                        ->schema([
                            TextInput::make('idioma')->label('Idioma')
                            ->required()
                            ->maxLength(100),
                            Select::make('nivel')->label('Nivel')
                            ->required()
                            ->options(IdiomaNivelEnum::class),
                        ]),
                        
                    ])
                ]),
                Tab::make('Tab 2')->label('Papeles')
                ->id('legajo')
                ->schema([
                    Repeater::make('Legajo')->relationship('legajos')
                    ->extraAttributes([
                        'style' => 'max-height: 450px; overflow-y: auto;', 
                    ])
                    ->hiddenLabel()
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        Tabs::make('Legajos')
                        ->columnSpanFull()
                        ->columns(3)
                        ->tabs([
                            Tab::make('Legajo')
                            ->schema([
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

                                DateTimePicker::make('fecha_de_ingreso')
                                ->validationMessages([
                                    "required" => "Requiere introducir la Fecha de ingreso.",
                                ])
                                ->format('Y-m-d H:i:s')
                                ->helperText('Si no introduce la fecha de ingreso, se asigna la fecha de hoy.')
                                ->dehydrateStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('Y-m-d H:i:s') : Carbon::now()),
                            ]),
                            Tab::make('Documento')
                            ->schema([
                                Repeater::make('Documento')->relationship('Documentos')
                                ->hiddenLabel()
                                ->columnSpanFull()
                                ->columns(2)
                                ->schema([
                                    Grid::make(1)
                                    ->schema([
                                        Textarea::make('descripcion')
                                        ->label('Descripción')
                                        ->required()
                                        ->columnSpanFull(),
                                        Select::make('tipodoc')
                                        ->label('Tipo de Documento.')
                                        ->required()
                                        ->options(TipodocEnum::class),
                                        DateTimePicker::make('fecha_de_creacion')
                                        ->label('Fecha de Creación.')
                                        ->format('Y-m-d H:i:s')
                                        ->helperText('Si no introduce la fecha de creación, se asigna la fecha de hoy.')
                                        ->dehydrateStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('Y-m-d H:i:s') : Carbon::now()),
                                    ]),
                                    Grid::make(1)
                                    ->schema([
                                        FileUpload::make('archivo')
                                        ->label('Documento Adjunto')
                                        ->disk('public') // Disco de almacenamiento (config/filesystems.php)
                                        ->directory('documentos/') // Carpeta destino dentro del disco
                                        ->visibility('public') // Visibilidad del archivo
                                        ->acceptedFileTypes(['application/pdf', 'image/*']) // Restringir formatos
                                        ->maxSize(10240) // Tamaño máximo en KB (10 MB)
                                        ->required(),
                                    ]),
                                ])
                            ]),
                        ]),
                        
                    ]),
                ]),
                Tab::make('Tab 3')->label('Estudios/Títulos')
                ->id('estudio')
                ->columnSpanFull()
                ->schema([
                    Repeater::make('Estudio')->relationship('estudios') // Relación Principal (Persona -> Estudios)
                    ->columns(1) // Cambiado a 1 para que las pestañas ocupen todo el ancho
                    ->hiddenLabel()
                    ->columnSpanFull()
                    ->addActionLabel('Añadir un Estudio')
                    ->extraAttributes([
                        'style' => 'max-height: 552px; overflow-y: auto;', 
                    ])
                    ->schema([
                        Tabs::make('Estudios')
                        ->schema([
                            Tab::make('Estudio')
                            ->columns(3)
                            ->schema([
                                /*TextInput::make('nombre')->label('Nombre')
                                ->required()
                                ->validationMessages(["required" => "Requiere introducir el Nombre del Estudio."]),*/
                                Select::make("nivel_estudio")->label("Nivel de Estudio")
                                ->options(NivelEstudioEnum::class)
                                ->required()
                                ->live()
                                ->validationMessages(["required" => "Requiere seleccionar el Nivel de Estudio."]),

                                TextInput::make('institucion')->label('Institución')
                                ->required(fn (Get $get): bool => 
                                    $get('nivel_estudio') !== null && 
                                    !in_array($get('nivel_estudio'), [0, '0', NivelEstudioEnum::SinEstudio], true)
                                )
                                ->disabled(fn (Get $get): bool => 
                                    $get('nivel_estudio') === null || 
                                    in_array($get('nivel_estudio'), [0, '0', NivelEstudioEnum::SinEstudio], true)
                                )
                                ->validationMessages(["required" => "Requiere introducir la Institución."]),

                                DatePicker::make('fecha_fin')->label('Fecha de Finalización')
                                ->required(function (Get $get): bool { // Cambiado fn por function
                                    $value = $get('nivel_estudio');
                                    if ($value === null) return false;

                                    // Array de casos que NO tienen fecha de finalización
                                    $sinFechaFin = [
                                        0, '0', NivelEstudioEnum::SinEstudio,
                                        1, '1', NivelEstudioEnum::Primario_No,
                                        3, '3', NivelEstudioEnum::Secundario_No,
                                        5, '5', NivelEstudioEnum::Terciario_No,
                                    ];
                                    return !in_array($value, $sinFechaFin, true);
                                })
                                ->disabled(function (Get $get): bool { // Cambiado fn por function
                                    $value = $get('nivel_estudio');
                                    if ($value === null) return true;

                                    // Array de casos que deshabilitan la fecha de finalización
                                    $sinFechaFin = [
                                        0, '0', NivelEstudioEnum::SinEstudio,
                                        1, '1', NivelEstudioEnum::Primario_No,
                                        3, '3', NivelEstudioEnum::Secundario_No,
                                        5, '5', NivelEstudioEnum::Terciario_No,
                                    ];
                                    return in_array($value, $sinFechaFin, true);
                                })
                                ->validationMessages(["required" => "Requiere introducir la Fecha de Finalización."]),
                            ]),
                                
                            Tab::make('Título')
                            ->label('Títulos')
                            //->visible(fn (Get $get): bool => $get('nivel_estudio') !== null && $get('nivel_estudio') != NivelEstudioEnum::SinEstudio)
                            ->visible(function (Get $get): bool {
                                $value = $get('nivel_estudio');

                                // Si no se seleccionó nada, ocultar la pestaña
                                if ($value === null) {
                                    return false;
                                }

                                // Array con los casos que NO deben pedir título (por valor y por instancia)
                                $excluidos = [
                                    0, '0', NivelEstudioEnum::SinEstudio,
                                    1, '1', NivelEstudioEnum::Primario_No,
                                    3, '3', NivelEstudioEnum::Secundario_No,
                                    5, '5', NivelEstudioEnum::Terciario_No,
                                ];

                                // Retorna TRUE (visible) solo si el valor seleccionado NO está en los excluidos
                                return !in_array($value, $excluidos, true);
                            })
                            ->schema([
                                Repeater::make('titulos')->relationship('titulos') 
                                ->addActionLabel('Añadir un Título')
                                ->hiddenLabel()
                                ->columns(1)
                                ->schema([
                                    TextInput::make('nombre')
                                    ->label('Nombre del Título')
                                    ->required()
                                    ->validationMessages(["required" => "Requiere introducir la Institución."]),
                                ])
                            ])
                        ]),
                    ])
                ]),
                Tab::make('Tab 4')->label('Cursos')
                ->id('curso')
                ->columnSpanFull()
                ->schema([
                    Repeater::make('Curso')->relationship('cursos')
                    ->hiddenLabel()
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        TextInput::make('nombre')->label('Nombre del curso')
                        ->required()
                        ->maxLength(255),
                        TextInput::make('institucion')->label('Institución')
                        ->maxLength(255),
                        TextInput::make('duracion')->label('Duración'),
                        DatePicker::make('fecha')->label('Fecha'),
                        Checkbox::make('tiene_certificado')->label('Tiene certificado'),
                    ])
                ]),
                Tab::make('Tab 5')
                ->id('antecedenteslaborales')
                ->label('Antecedentes Laborales')
                ->columnSpanFull()
                ->schema([
                    Repeater::make('Laboral')->relationship('antecedentesLaborales')
                    ->hiddenLabel()
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        TextInput::make('empleador')->label('Empleador')
                        ->required(),
                        TextInput::make('lugar_de_trabajo')->label('Lugar de trabajo')
                        ->required(),
                        TextInput::make('cargo')->label('Cargo'),
                        DatePicker::make('fecha_inicio')->label('Fecha de inicio'),
                        DatePicker::make('fecha_fin')->label('Fecha de fin'),
                        TextInput::make('motivo_egreso')->label('Motivo de egreso'),
                    ])
                ]),
                Tab::make('Tab 6')
                ->label('Usuario')
                ->columnSpanFull()
                ->visible(auth()->user()->isAdmin_RRHH())
                ->schema([
                    Section::make('Usuario')
                    ->columnSpanFull()
                    ->relationship('Usuario')
                    ->schema([
                        TextInput::make('name')->label('Nombre de Usuario')
                        ->required(),
                        
                        TextInput::make('email')->label('Correo Electrónico')
                        ->email()
                        ->default(function () {
                            // Captura el 'persona_id' enviado desde la URL
                            $personaId = request()->query('persona_id');
                            
                            if ($personaId) {
                                // Busca la persona y retorna su correo
                                return Persona::find($personaId)?->email;
                            }
                            
                            return null;
                        })
                        ->required(),
                        TextInput::make('password')
                        ->label('Contraseña')
                        ->password()
                        ->required(fn (string $context): bool => $context === 'create')
                        // Si el campo está vacío al guardar, no lo incluye en el Query de actualización
                        ->dehydrated(fn (?string $state) => filled($state))

                        // Encripta la contraseña automáticamente antes de guardarla (si se modificó)
                        ->mutateDehydratedStateUsing(fn (string $state) => Hash::make($state))
                        ->helperText(function (string $context) {
                            if ($context === 'edit') {
                                return 'Deje este campo en blanco si no desea cambiar la contraseña.';
                            }
                            return null; // No muestra nada al crear
                        }),

                        Select::make('rol')->label('Rol del Usuario')
                        ->options([
                            1 => 'Empleado',
                            2 => 'Funcionario',
                            3 => 'RRHH (Recursos Humanos.)',
                            //4 => 'Administrador',
                        ]) // 'Empleado', 'Funcionario', 'RRHH', 'Administrador'
                        ->required(),
                    ]),
                ]),
            ]),
        ]);
    }
}
