<?php

namespace App\Models;

use App\Enums\NivelEstudioEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Estudio extends Model
{
    protected $casts = [
        'nivel_estudio' => NivelEstudioEnum::class,
    ];
    protected $fillable = [
        'nivel_estudio',
        'fecha_fin',
        'persona_id',
        'institucion_id',
        'institucion_otro'
    ];
    public function persona(){ return $this->belongsTo(Persona::class, 'persona_id'); }
    public function instituciones() { return $this->hasMany(Institucion::class); }
    public function titulos() { return $this->belongsToMany(Titulo::class, 'estudio_titulo')->withTimestamps(); }
    public function estudioTitulos() { return $this->hasMany(EstudioTitulo::class); }
    public function institucion() { return $this->belongsTo(Institucion::class); }
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
    public function getInstitucionMostradaAttribute(): ?string
    {
        return $this->institucion?->nombre ?? $this->institucion_otro;
    }
    public function scopeConTitulo(Builder $query): Builder
    {
        return $query->whereIn('estudios.nivel_estudio', NivelEstudioEnum::conTitulo());
    }

    /** El estudio de mayor nivel de cada persona (a igualdad, el de fecha más reciente). */
    public function scopeMayorNivel(Builder $query): Builder
    {
        return $query->conTitulo()->where('estudios.id', '=', self::subconsultaMayor());
    }

    /** Todos los demás estudios con título, de mayor a menor nivel. */
    public function scopeOtrosNiveles(Builder $query): Builder
    {
        return $query->conTitulo()
        ->where('estudios.id', '<>', self::subconsultaMayor())
        ->orderByDesc('estudios.nivel_estudio')
        ->orderByDesc('estudios.fecha_fin');
    }
    public function scopePendientes($query)
    {
        return $query->whereNull('institucion_id')->whereNotNull('institucion_otro');
    }
    private static function subconsultaMayor(): \Closure
    {
        return fn ($q) => $q->select('e2.id')
        ->from('estudios as e2')
        ->whereColumn('e2.persona_id', 'estudios.persona_id')
        ->whereIn('e2.nivel_estudio', NivelEstudioEnum::conTitulo())
        ->orderByDesc('e2.nivel_estudio')
        ->orderByDesc('e2.fecha_fin')
        ->orderByDesc('e2.id')
        ->limit(1);
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

            ...Institucion::getFormSchema(
                required: fn (Get $get) => self::tieneEstudiosActivos($get('nivel_estudio')),
                hidden: fn (Get $get) => ! self::tieneEstudiosActivos($get('nivel_estudio')),
            ),

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
                TextEntry::make('institucion_mostrada')
                ->label('Institución')
                ->placeholder('-')
                ->formatStateUsing(fn ($state, $record) => ($state && !$record->institucion_id) ? "{$state} (pendiente de revisión)" : $state)
                ->color(fn ($record) => (!$record->institucion_id && $record->institucion_otro) ? 'warning' : null),

                TextEntry::make('nivel_estudio')->label('Nivel de Estudio'),
                TextEntry::make('fecha_fin')->label('Fecha de Finalización')->placeholder('-')->date('d/m/Y'),

                RepeatableEntry::make('estudioTitulos')
                    ->label('Títulos Obtenidos')
                    ->columnSpanFull()
                    ->grid(2)
                    ->placeholder('Sin títulos registrados para este estudio')
                    ->schema([
                        TextEntry::make('nombre_mostrado')
                        ->hiddenLabel()
                        ->icon('heroicon-m-academic-cap')
                        //->state(fn (EstudioTitulo $record) => "#{$record->id} | titulo_id=" . var_export($record->titulo_id, true) . " | otro=" . var_export($record->titulo_otro, true))
                        ->state(fn (EstudioTitulo $record) => $record->titulo?->nombre ?? $record->titulo_otro)
                        ->formatStateUsing(fn ($state, EstudioTitulo $record) => $record->titulo_id ? $state : "{$state} (pendiente de revisión)")
                        ->color(fn (EstudioTitulo $record) => $record->titulo_id ? null : 'warning'),
                    ]),
            ],
        };
        return $resultado;
    }
    public static function getRepeaterSchema(): array
    {
        return [
            Repeater::make('estudios')
            ->relationship('estudios')
            ->hiddenLabel()
            ->columnSpanFull()
            ->addActionLabel('Agregar un Estudio')
            ->addAction(fn (Action $action) => $action->icon('heroicon-m-plus')->color('gray'))
            ->collapsible()
            ->defaultItems(0)
            ->itemLabel(fn (array $state): ?string => ($state['institucion'] ?? null) ?: 'Nuevo Registro de Estudio')
            ->schema([
                Grid::make(['default' => 1, 'sm' => 3])->schema(Estudio::getFormSchema()),

                Section::make('Títulos Obtenidos')
                ->compact()
                ->collapsible()
                ->visible(fn (Get $get) => Estudio::requiereTitulo($get('nivel_estudio')))
                ->extraAttributes([ /* tu style igual */ ])
                ->schema([
                    Repeater::make('titulos')
                    ->relationship('estudioTitulos')
                    ->addActionLabel('Agregar un Título')
                    ->itemLabel('Nuevo Registro de Título')
                    ->hiddenLabel()
                    ->collapsible()
                    ->grid(3)
                    ->schema(EstudioTitulo::getFormSchema()),
                ]),
            ]),
        ];
    }
}
