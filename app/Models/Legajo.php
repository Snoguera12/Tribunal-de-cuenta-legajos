<?php

namespace App\Models;

use App\Enums\EstadoLegajoEnum;
use App\Enums\TipoContratoEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;


class Legajo extends Model
{
    protected $attributes = [
        "estado" => true,
    ];
    protected $casts = [
        "estado" => EstadoLegajoEnum::class,
        "tipo_contrato" => TipoContratoEnum::class,
    ];
    protected $fillable = [
        "num_legajo",
        "estado",
        "fecha_de_ingreso",
        "tipo_contrato",
        "persona_id",
        "categoria_id",
        "cargo_id",
        "area_id",
    ];
    public function historial(){
        return $this->hasMany(Historialbaja::class);
    }
    public function persona(){
        return $this->belongsTo(Persona::class, 'persona_id');
    }
    public function categoria(){
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }
    public function cargo(){
        return $this->belongsTo(Cargo::class, 'cargo_id');
    }
    public function area(){
        return $this->belongsTo(Area::class, 'area_id');
    }
    public function documentos()
    {
        return $this->hasMany(Documento::class);
    }
    public function isAlta()
    {
        return $this->estado == EstadoLegajoEnum::Alta;
    }
    public function getIcon()
    {
        return $this->isAlta() ? Heroicon::CheckCircle : Heroicon::XCircle;
    }
    public function getColor()
    {
        return $this->isAlta() ? 'success' : 'danger';
    }
    
    public static function getFormSchema(bool $visible_persona_id): array{
        
        $resultado = [
            Select::make('persona_id')->label('Persona')
            ->required()
            ->searchable()
            ->visible($visible_persona_id)
            ->options(Persona::getPersonas())
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

            TextInput::make('num_legajo')
            ->label('Número de legajo')
            ->placeholder('Ej. 1045')
            ->required()
            ->numeric()
            ->unique(table: 'legajos', column: 'num_legajo', ignoreRecord: true) // Corregido: ignoreRecord evita fallos al editar
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
            ->options(TipoContratoEnum::class)
            ->validationMessages(['required' => 'Seleccione el tipo de contratación.']),

            Select::make("area_id")
            ->label("Nombre del Área")
            ->searchable()
            ->required()
            ->options(Area::getAreas())
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
            ->options(Cargo::getCargos())
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
            ->options(Categoria::getCategorias())
            ->validationMessages(['required' => 'Debe asociar una categoría.']),

            DateTimePicker::make('fecha_de_ingreso')
            ->label('Fecha de Ingreso')
            ->native(false)
            ->format('Y-m-d H:i:s')
            ->placeholder('Hoy (Si se deja vacío)')
            ->helperText('Si no introduce la fecha de ingreso, se asigna la fecha de hoy.')
            ->dehydrateStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('Y-m-d H:i:s') : Carbon::now())
            ->validationMessages([
                "required" => "Requiere introducir la Fecha de ingreso.",
            ]),            
        ];

        return $resultado;
    }

    public static function getOutSchema(string $mode_out) : array{
        $resultado = match ($mode_out) {
            'table' => [
                TextColumn::make('num_legajo')->label('Número')
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('persona.nombre')->label("Nombre")
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('persona.apellido')->label("Apellido")
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('persona.dni')->label("DNI")
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('persona.nivel_estudio') // O el campo exacto donde guardes el título
                ->label('Estudio')
                ->sortable()
                ->searchable()
                ->placeholder('Sin registrar') // Si está vacío, muestra esto de forma prolija
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('estado')
                ->icon(fn (Legajo $legajo) => $legajo->getIcon())
                ->color(fn (Legajo $legajo) => $legajo->getColor())
                ->iconColor(fn (Legajo $legajo) => $legajo->getColor())
                ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('tipo_contrato')
                ->label('Contratación')
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('area.nombre')
                ->label("Área")
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('cargo.nombre')
                ->label('Cargo')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('categoria.id')
                ->label("Categoría")
                ->sortable()
                ->formatStateUsing(Categoria::getNombre())
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('fecha_de_ingreso')
                ->dateTime('d/m/Y')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            ],
            'list' => [
                TextEntry::make('num_legajo')
                ->label('Número de legajo'),

                TextEntry::make('fecha_de_ingreso')
                ->label('Fecha de Ingreso'),

                TextEntry::make('estado')
                ->label('Estado')
                ->icon(fn (Legajo $record) => $record->isAlta() ? Heroicon::CheckCircle : Heroicon::XCircle)
                ->color(fn (Legajo $record) => $record->isAlta() ? 'success' : 'danger')
                ->iconColor(fn (Legajo $record) => $record->isAlta() ? 'success' : 'danger')
                ->placeholder('-'),

                TextEntry::make('tipo_contrato')
                ->label('Tipo de Contratación'),

                TextEntry::make('area.nombre')
                ->label('Área'),

                TextEntry::make('categoria.nombre')
                ->label('Categoría'),

                TextEntry::make('cargo.nombre')
                ->label('Cargo'),
            ],
        };

        return $resultado;
    }

    public static function getFrontLegajoDocumentos(string $formmode) : array{
        // BLOQUE 1: Datos Administrativos del Legajo
        $resutlado = match ($formmode) {
            'mul' => [
                Grid::make([
                    'default' => 1,
                    'sm' => 3,
                ])->schema(Legajo::getFormSchema(false)),
                
            ],
            'solo' => [
                Section::make()
                ->columns(3)
                ->columnSpanFull()
                ->schema(Legajo::getFormSchema(true)),
            ],
        };

        $resutlado = [
            ...$resutlado, // Esos tres puntos en la variable $resultado es un "operador de propagación" (spread operator)

            // BLOQUE 2: Sección Integrada de Documentos Adjuntos
            Section::make('Documentación Digitalizada')
            ->description('Cargue los archivos adjuntos y documentos que respaldan este legajo.')
            ->icon('heroicon-o-document-arrow-up')
            ->collapsible() // El usuario puede ocultar la zona de archivos si no la necesita en el momento
            ->columnSpanFull()
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
                Repeater::make('Documento')
                ->relationship('Documentos')
                ->hiddenLabel()
                ->columnSpanFull()
                ->collapsible()
                ->addActionLabel('Adjuntar un Documento')
                ->itemLabel('Nuevo Documento')
                ->grid(3) // Mantiene tus dos columnas de documentos lado a lado
                ->schema([
                    // SOLUCIÓN NATIVA: Usamos un Fieldset o una Section interna. 
                    // Cada vez que se crea un documento, Filament genera este recuadro contenedor.
                    Grid::make(1)
                    ->columnSpan(1)
                    ->schema(Documento::getFromSchema(false, false)),
                ]),
            ]),
        ];
        return $resutlado;
    }
}