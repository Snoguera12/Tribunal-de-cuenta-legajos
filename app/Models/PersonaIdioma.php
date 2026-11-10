<?php

namespace App\Models;

use App\Enums\IdiomaNivelEnum;
use App\Models\Concerns\NoSeBorra;
use App\Filament\Forms\Components\RepeaterAdjuntable;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PersonaIdioma extends Model
{
    use NoSeBorra;

    public const OTRO = '__otro__';

    protected $table = 'persona_idiomas';

    protected $casts = [
        'nivel' => IdiomaNivelEnum::class,
    ];

    protected $fillable = [
        'persona_id',
        'idioma_id',
        'idioma_otro',
        'nivel',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }

    public function idioma()
    {
        return $this->belongsTo(Idioma::class);
    }

    public function getNombreMostradoAttribute(): string
    {
        return $this->idioma?->nombre ?? $this->idioma_otro ?? '-';
    }

    public function scopePendientes($query)
    {
        return $query->whereNull('idioma_id')->whereNotNull('idioma_otro');
    }

    public function getEtiquetaAdjuntarAttribute(): string
    {
        if ($this->idioma_id) {
            return "Idioma {$this->idioma?->nombre}";
        }

        return "Idioma {$this->idioma_otro} - en revisión";
    }

    /** Asigna registros pendientes a un idioma del catálogo. Si la persona ya lo tenía, el duplicado queda desadjuntado (no se borra). */
    public static function asignarAIdioma(Collection $registros, int $idiomaId): void
    {
        DB::transaction(function () use ($registros, $idiomaId) {
            foreach ($registros as $registro) {
                $yaLoTiene = static::where('persona_id', $registro->persona_id)
                    ->where('idioma_id', $idiomaId)
                    ->whereKeyNot($registro->getKey())
                    ->exists();

                $registro->update($yaLoTiene
                    ? ['persona_id' => null, 'idioma_id' => $idiomaId, 'idioma_otro' => null]
                    : ['idioma_id' => $idiomaId, 'idioma_otro' => null]);
            }
        });
    }

    public static function getFormSchema(): array
    {
        return [
            Select::make('idioma_id')
            ->label('Idioma')
            ->searchable()
            ->live()
            ->required()
            ->options(fn () => [self::OTRO => 'Otro (especificar)']
                + Idioma::orderBy('nombre')->pluck('nombre', 'id')->all())
            ->formatStateUsing(fn ($state, ?PersonaIdioma $record) =>
                blank($state) && filled($record?->idioma_otro) ? self::OTRO : $state)
            ->dehydrateStateUsing(fn ($state) => $state === self::OTRO ? null : $state),

            TextInput::make('idioma_otro')
            ->label('Escriba el idioma')
            ->helperText('Use esta opción solo si el idioma no está en la lista.')
            ->maxLength(100)
            ->visible(fn (Get $get) => $get('idioma_id') === self::OTRO)
            ->required(fn (Get $get) => $get('idioma_id') === self::OTRO)
            ->dehydratedWhenHidden()
            ->dehydrateStateUsing(fn ($state, Get $get) => $get('idioma_id') === self::OTRO ? Str::squish((string) $state) : null),

            Select::make('nivel')
            ->label('Nivel')
            ->options(IdiomaNivelEnum::class)
            ->required(),
        ];
    }

    public static function getOutSchema(string $mode_out): array
    {
        return match ($mode_out) {
            'table' => [],
            'list' => [
                TextEntry::make('nombre_mostrado')
                ->label('Idioma')
                ->icon('heroicon-m-language')
                ->formatStateUsing(fn ($state, PersonaIdioma $record) => $record->idioma_id ? $state : "{$state} (pendiente de revisión)")
                ->color(fn (PersonaIdioma $record) => $record->idioma_id ? null : 'warning'),

                TextEntry::make('nivel')->label('Nivel'),
            ],
        };
    }

    public static function getRepeaterSchema(): array
    {
        return [
            RepeaterAdjuntable::make('idiomas')
                ->adjuntable(fn ($r) => $r->etiqueta_adjuntar)
                ->relationship('idiomas')
                ->hiddenLabel()
                ->columnSpanFull()
                ->addActionLabel('Agregar un Idioma')
                ->itemLabel(fn (array $state): ?string => ($state['idioma_otro'] ?? null)
                    ?: (Idioma::find($state['idioma_id'] ?? null)?->nombre ?? 'Nuevo Registro de Idioma'))
                ->defaultItems(0)
                ->collapsible()
                ->grid(3)
                ->addAction(fn (Action $action) => $action->icon('heroicon-m-plus')->color('gray'))
                ->schema([
                    Grid::make(1)
                        ->columnSpan(1)
                        ->schema(PersonaIdioma::getFormSchema()),
                ])
                ->rule(fn () => function (string $attribute, $value, \Closure $fail) {
                    $ids = collect($value)->pluck('idioma_id')
                        ->filter(fn ($id) => filled($id) && $id !== PersonaIdioma::OTRO);

                    $textos = collect($value)
                        ->filter(fn ($item) => ($item['idioma_id'] ?? null) === PersonaIdioma::OTRO)
                        ->map(fn ($item) => mb_strtolower(Str::squish((string) ($item['idioma_otro'] ?? ''))))
                        ->filter();

                    if ($ids->count() !== $ids->unique()->count() || $textos->count() !== $textos->unique()->count()) {
                        $fail('No puede repetir el mismo idioma en una persona.');
                    }
                }),
        ];
    }
}