<?php
namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Institucion extends Model
{
    
    private const OTRO = '__otro__';
    protected $fillable = [
        'nombre',
    ];
    public function estudios() {
        return $this->hasMany(Estudio::class);
    }
    public function cursos() {
        return $this->hasMany(Curso::class);
    }
    public static function consultaPendientes(): Builder
    {
        // id único: pares para estudios, impares para cursos
        $estudios = DB::table('estudios')
            ->whereNull('institucion_id')
            ->whereNotNull('institucion_otro')
            ->selectRaw("id * 2 as id, 'Estudio' as origen, id as registro_id,
                institucion_otro, nivel_estudio, NULL as curso, created_at");

        $cursos = DB::table('cursos')
            ->whereNull('institucion_id')
            ->whereNotNull('institucion_otro')
            ->selectRaw("id * 2 + 1 as id, 'Curso' as origen, id as registro_id,
                institucion_otro, NULL as nivel_estudio, nombre as curso, created_at");

        return static::query()->fromSub($estudios->unionAll($cursos), (new static)->getTable());
    }

    public static function asignarPendientes(\Illuminate\Support\Collection $pendientes, int $institucionId): void
    {
        DB::transaction(function () use ($pendientes, $institucionId) {
            foreach (['Estudio' => Estudio::class, 'Curso' => Curso::class] as $origen => $modelo) {
                $ids = $pendientes->where('origen', $origen)->pluck('registro_id');

                if ($ids->isNotEmpty()) {
                    $modelo::whereIn('id', $ids)->update([
                        'institucion_id' => $institucionId,
                        'institucion_otro' => null,
                    ]);
                }
            }
        });
    }
    public function fusionarEn(Institucion $destino): void
    {
        DB::transaction(function () use ($destino) {
            Estudio::where('institucion_id', $this->id)->update(['institucion_id' => $destino->id]);
            Curso::where('institucion_id', $this->id)->update(['institucion_id' => $destino->id]);
            $this->delete();
        });
    }
    public static function getAbmFormSchema(): array
    {
        return [
            TextInput::make('nombre')
                ->label('Nombre de la institución')
                ->required()
                ->maxLength(300)
                ->unique(ignoreRecord: true)
                ->dehydrateStateUsing(fn ($state) => Str::squish((string) $state)),
        ];
    }

    public static function getOutSchema(string $mode_out): array
    {
        return match ($mode_out) {
            'table' => [
                TextColumn::make('nombre')->label('Institución')->searchable()->sortable(),
                TextColumn::make('estudios_count')->counts('estudios')->label('Estudios')->sortable(),
                TextColumn::make('cursos_count')->counts('cursos')->label('Cursos')->sortable(),
            ],
        };
    }
    public static function getFormSchema(bool|\Closure $required = false, bool|\Closure $hidden = false): array
    {
        return [
            Select::make('institucion_id')
            ->label('Institución')
            ->searchable()
            ->live()
            ->required($required)
            ->hidden($hidden)
            ->options(fn () => [self::OTRO => 'Otra (especificar)'] + self::orderBy('nombre')->pluck('nombre', 'id')->all())
            ->formatStateUsing(fn ($state, ?Model $record) => blank($state) && filled($record?->institucion_otro) ? self::OTRO : $state)
            ->dehydrateStateUsing(fn ($state) => $state === self::OTRO ? null : $state),

            TextInput::make('institucion_otro')
            ->label('Escriba la institución')
            ->helperText('Use esta opción solo si la institución no está en la lista.')
            ->maxLength(300)
            ->visible(fn (Get $get) => $get('institucion_id') === self::OTRO)
            ->required(fn (Get $get) => $get('institucion_id') === self::OTRO)
            ->dehydratedWhenHidden()
            ->dehydrateStateUsing(fn ($state, Get $get) => $get('institucion_id') === self::OTRO ? Str::squish((string) $state) : null),
        ];
    }
}
