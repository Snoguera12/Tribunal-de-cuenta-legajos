<?php

namespace App\Models;

use App\Enums\EstadoLegajoEnum;
use App\Enums\TipoContratoEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
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
    public function histrorial(){
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
            ->visible($visible_persona_id)
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
            ->options(Categoria::Opciones()),

            DateTimePicker::make('fecha_de_ingreso')->label('Fecha de Ingreso')
            ->helperText('Si no introduce la fecha de ingreso, se asigna la fecha de hoy.')
            ->validationMessages([
                "required" => "Requiere introducir la Fecha de ingreso.",
            ]),
        ];

        return $resultado;
    }
}
