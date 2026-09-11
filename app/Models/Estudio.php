<?php

namespace App\Models;

use App\Enums\NivelEstudioEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Model;

class Estudio extends Model
{
    protected $casts = [
        'nivel_estudio' => NivelEstudioEnum::class,
    ];
    protected $fillable = [
        'institucion',
        'nivel_estudio',
        'fecha_fin',
        'persona_id',
    ];
    public function persona(){
        return $this->belongsTo(Persona::class, 'persona_id');
    }
    public function titulos()
    {
        return $this->hasMany(Titulo::class);
    }
    protected static function tieneEstudiosActivos($value): bool{
        if ($value === null) return false;

        // Extrae el valor escalar si es una instancia de Enum
        $actualValue = $value instanceof \BackedEnum ? $value->value : $value;

        // Comparamos contra valores escalares y contra la instancia del Enum
        $excluidos = [0, '0', NivelEstudioEnum::SinEstudio, NivelEstudioEnum::SinEstudio->value];

        return !in_array($actualValue, $excluidos, true) && !in_array($value, $excluidos, true);
    }
    protected static function requiereFechaFin($value): bool{
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
    public static function getInstitucion() : array{
        return self::query()
        ->get(['id', 'institucion'])
        ->mapWithKeys(fn ($item) => [
            $item->id => "{$item->institucion}"
        ])
        ->toArray();
    }
    public static function getFormSchema() : array{
        $resultado = [
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
        ];
        return $resultado;
    }
    public static function getOutSchema(string $mode_out) : array{
        $resultado = match ($mode_out) {
            'table' => [
                
            ],
            'list' => [
                
            ],
        };
        return $resultado;
    }
}
