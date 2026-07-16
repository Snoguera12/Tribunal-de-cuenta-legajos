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
    protected static function tieneEstudiosActivos($value): bool
    {
        if ($value === null) return false;

        // Extrae el valor escalar si es una instancia de Enum
        $actualValue = $value instanceof \BackedEnum ? $value->value : $value;

        // Comparamos contra valores escalares y contra la instancia del Enum
        $excluidos = [0, '0', NivelEstudioEnum::SinEstudio, NivelEstudioEnum::SinEstudio->value];

        return !in_array($actualValue, $excluidos, true) && !in_array($value, $excluidos, true);
    }

    protected static function requiereFechaFin($value): bool
    {
        if (!self::tieneEstudiosActivos($value)) return false;

        $actualValue = $value instanceof \BackedEnum ? $value->value : $value;

        // Agrupamos todos los casos de exclusión (tanto instancias como valores puros)
        $sinFechaFin = [
            0, '0', NivelEstudioEnum::SinEstudio, NivelEstudioEnum::SinEstudio->value,
            1, '1', NivelEstudioEnum::Primario_No, NivelEstudioEnum::Primario_No->value,
            3, '3', NivelEstudioEnum::Secundario_No, NivelEstudioEnum::Secundario_No->value,
            5, '5', NivelEstudioEnum::Terciario_No, NivelEstudioEnum::Terciario_No->value,
        ];

        return !in_array($actualValue, $sinFechaFin, true) && !in_array($value, $sinFechaFin, true);
    }

    protected static function requiereTitulo($value): bool
    {
        return self::requiereFechaFin($value);
    }
    
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
                Tab::make('Tab 2')
                ->id('familiar')
                ->label('Familiares')
                ->schema([
                    Repeater::make('familiares')
                    ->relationship('familiares')
                    ->hiddenLabel()
                    ->columnSpanFull()
                    ->columns(2)
                    ->addActionLabel('Añadir Familiar')
                    ->schema([
                        TextInput::make('nombre')->label('Nombre')->required(),
                        TextInput::make('apellido')->label('Apellido')->required(),
                        TextInput::make('dni')->label('DNI')->required(),
                        DatePicker::make('fecha_de_nacimiento')->label('Fecha Nacimiento')->required(),
                        Select::make('parentesco')->label('Parentesco')->options(ParentescoEnum::class)->required(),
                        Select::make('vive')->label('Estado Vital')->options(FamiliarViveEnum::class)->required(),
                    ]),
                ]),
                Tab::make('Tab 3')
                ->id('idioma')
                ->label('Idiomas')
                ->schema([
                    Section::make('Idiomas')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('idiomas')->relationship('idiomas')->hiddenLabel()
                        ->columns(2)
                        ->addActionLabel('Añadir Idioma')
                        ->schema([
                            TextInput::make('idioma')->label('Idioma')->required()->maxLength(100),
                            Select::make('nivel')->label('Nivel')->options(IdiomaNivelEnum::class)->required(),
                        ]),
                    ]),
                ]),
                Tab::make('Legajos')
                ->id('legajo')
                ->label('Legajos') // Etiqueta más descriptiva para el usuario
                ->icon('heroicon-m-folder-open')
                ->schema([
                    Repeater::make('Legajo')
                        ->relationship('legajos')
                        ->hiddenLabel()
                        ->columnSpanFull()
                        ->collapsible() // Permite encoger legajos antiguos para mantener orden visual
                        //->cloneable()
                        ->addActionLabel('Añadir un Legajo')
                        ->itemLabel(fn (array $state): ?string => 
                            // Muestra un título claro en cada bloque (Ej: "Legajo N° 4512")
                            ($state['num_legajo'] ?? null) 
                                ? "Legajo N° " . $state['num_legajo'] 
                                : 'Nuevo Registro de Legajo'
                        )
                        ->schema([
                            // BLOQUE 1: Datos Administrativos del Legajo
                            Grid::make([
                                'default' => 1,
                                'sm' => 3, // Distribución limpia en 3 columnas
                            ])->schema([
                                TextInput::make('num_legajo')
                                    ->label('Número de Legajo')
                                    ->placeholder('Ej. 1045')
                                    ->required()
                                    ->numeric()
                                    ->unique(table: 'legajos', column: 'num_legajo', ignoreRecord: true) // Corregido: ignoreRecord evita fallos al editar
                                    ->rules(['gt:0'])
                                    ->validationMessages([
                                        'required' => 'El número de legajo es obligatorio.',
                                        'unique' => 'Este número de legajo ya está registrado.',
                                        'gt' => 'El número debe ser mayor a cero.',
                                    ]),
                                Select::make('tipo_contrato')
                                    ->label('Tipo de Contratación')
                                    ->required()
                                    ->options(TipoContratoEnum::class)
                                    ->validationMessages(['required' => 'Seleccione el tipo de contratación.']),

                                Select::make('area_id')
                                    ->label('Área')
                                    ->searchable()
                                    ->required()
                                    ->options(fn () => Area::pluck('nombre', 'id')) // Optimizado: Carga diferida (lazy load)
                                    ->validationMessages(['required' => 'Debe asociar un área.']),

                                Select::make('cargo_id')
                                    ->label('Cargo')
                                    ->searchable()
                                    ->required()
                                    ->options(fn () => Cargo::pluck('nombre', 'id')) // Optimizado
                                    ->validationMessages(['required' => 'Debe asociar un cargo.']),

                                Select::make('categoria_id')
                                    ->label('Categoría')
                                    ->searchable()
                                    ->required()
                                    ->options(fn () => Categoria::selectRaw("id, nombre || ' ' || descripcion AS nombre_completo")
                                        ->pluck('nombre_completo', 'id')
                                    ) // Optimizado
                                    ->validationMessages(['required' => 'Debe asociar una categoría.']),

                                DateTimePicker::make('fecha_de_ingreso')
                                    ->label('Fecha de Ingreso')
                                    ->native(false)
                                    ->format('Y-m-d H:i:s')
                                    ->placeholder('Hoy (Si se deja vacío)')
                                    ->helperText('Si se deja vacío, se asignará la fecha y hora actual.')
                                    ->dehydrateStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('Y-m-d H:i:s') : Carbon::now()),
                            ]),

                            // BLOQUE 2: Sección Integrada de Documentos Adjuntos (Reemplaza a la pestaña)
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
                                    ->grid(2) // Mantiene tus dos columnas de documentos lado a lado
                                    ->schema([
                                        // SOLUCIÓN NATIVA: Usamos un Fieldset o una Section interna. 
                                        // Cada vez que se crea un documento, Filament genera este recuadro contenedor.
                                        Section::make()
                                            ->columns(1) // Distribuye el contenido internamente en 2 columnas
                                            ->schema([
                                                // Columna Izquierda: Metadatos del documento
                                                Grid::make(1)
                                                    ->columnSpan(1)
                                                    ->schema([
                                                        Select::make('tipodoc')
                                                            ->label('Tipo de Documento')
                                                            ->required()
                                                            ->native(false)
                                                            ->options(TipodocEnum::class)
                                                            ->validationMessages(['required' => 'Seleccione el tipo de documento.']),
                                                        Textarea::make('descripcion')
                                                            ->label('Descripción / Notas')
                                                            ->placeholder('Ej. Copia certificada del título...')
                                                            ->required()
                                                            ->rows(2),
                                                    ]),
                                                
                                                // Columna Derecha: Zona de arrastre de archivos
                                                Grid::make(1)
                                                    ->columnSpan(1)
                                                    ->schema([
                                                        FileUpload::make('archivo')
                                                            ->label('Documento Adjunto (PDF o Imagen)')
                                                            ->disk('public')
                                                            ->directory('documentos')
                                                            ->visibility('public')
                                                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                                                            ->maxSize(10240)
                                                            ->required()
                                                            ->downloadable()
                                                            ->openable()
                                                            ->validationMessages(['required' => 'Debe subir un archivo válido.']),
                                                    ]),
                                            ]),
                                    ]),
                                ]),
                        ]),
                ]),

                Tab::make('Tab 3')
                ->label("Estudios/Títulos")
                ->id('estudio')
                ->icon('heroicon-m-academic-cap')
                ->columnSpanFull()
                ->schema([
                    Repeater::make('Estudio')
                    ->relationship('estudios') 
                    ->hiddenLabel()
                    ->columnSpanFull()
                    ->addActionLabel('Añadir un Estudio')
                    ->collapsible()
                    ->defaultItems(0)
                    ->itemLabel(fn (array $state): ?string => 
                        ($state['institucion'] ?? null) 
                            ? ($state['institucion']) 
                            : 'Nuevo Registro de Estudio'
                    )
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'sm' => 3,
                        ])->schema([
                            Select::make('nivel_estudio')
                                ->label('Nivel de Estudio')
                                ->options(NivelEstudioEnum::class)
                                ->required()
                                ->live()
                                ->native(true) // Al usar el select nativo, el navegador evita que el scroll corte la lista
                                ->validationMessages(['required' => 'Debe seleccionar el nivel de estudio.']),


                            TextInput::make('institucion')
                                ->label('Institución')
                                ->placeholder('Ej. Universidad Nacional')
                                ->required(fn (Get $get) => self::tieneEstudiosActivos($get('nivel_estudio')))
                                ->hidden(fn (Get $get) => !self::tieneEstudiosActivos($get('nivel_estudio')))
                                ->validationMessages(['required' => 'Ingrese el nombre de la institución.']),

                            DatePicker::make('fecha_fin')
                                ->label('Fecha de Finalización')
                                ->maxDate(now())
                                ->required(fn (Get $get) => self::requiereFechaFin($get('nivel_estudio')))
                                ->hidden(fn (Get $get) => !self::requiereFechaFin($get('nivel_estudio')))
                                
                                ->validationMessages(['required' => 'Seleccione la fecha de finalización.']),
                        ]),

                        Section::make('Títulos Obtenidos')
                            ->compact()
                            ->visible(fn (Get $get) => self::requiereTitulo($get('nivel_estudio')))
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
                                Repeater::make('titulos')
                                    ->relationship('titulos') 
                                    ->addActionLabel('Añadir un Título')
                                    ->hiddenLabel()
                                    ->grid(2)
                                    ->schema([
                                        TextInput::make('nombre')
                                            ->label('Nombre del Título')
                                            ->placeholder('Ej. Licenciado en Administración')
                                            ->required()
                                            ->validationMessages(['required' => 'Ingrese el nombre del título.']),
                                    ])
                            ])
                    ]),

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

