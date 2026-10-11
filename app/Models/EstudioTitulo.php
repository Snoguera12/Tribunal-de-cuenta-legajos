<?php
namespace App\Models;

use DB;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class EstudioTitulo extends Model
{
    private const OTRO = '__otro__';
    protected $table = 'estudio_titulos';
    protected $fillable = ['estudio_id', 'titulo_id', 'titulo_otro'];

    public function estudio() {
        return $this->belongsTo(Estudio::class, 'estudio_id');
    }
    public function titulo()  {
        return $this->belongsTo(Titulo::class, 'titulo_id');
    }

    public function getNombreMostradoAttribute(): string
    {
        return $this->titulo?->nombre ?? '-';
    }
    public function scopePendientes($query)
    {
        return $query->whereNull('titulo_id')->whereNotNull('titulo_otro');
    }
    /** Asigna registros pendientes a un título del catálogo, respetando el índice único (estudio_id, titulo_id). */
    public static function asignarATitulo(Collection $registros, int $tituloId): void
    {
        DB::transaction(function () use ($registros, $tituloId) {
            foreach ($registros as $registro) {
                $yaLoTiene = static::where('estudio_id', $registro->estudio_id)
                ->where('titulo_id', $tituloId)
                ->whereKeyNot($registro->getKey())
                ->exists();

                $yaLoTiene
                    ? $registro->delete()
                    : $registro->update(['titulo_id' => $tituloId, 'titulo_otro' => null]);
            }
        });
    }
    public static function getFormSchema(): array
    {
        return [
            Select::make('titulo_id')
            ->label('Título')
            ->searchable()
            ->live()
            ->required()
            ->options(fn () => [self::OTRO => 'Otro (especificar)']
                + Titulo::orderBy('nombre')->pluck('nombre', 'id')->all())
            ->formatStateUsing(fn ($state, ?EstudioTitulo $record) =>
                blank($state) && filled($record?->titulo_otro) ? self::OTRO : $state)
            ->dehydrateStateUsing(fn ($state) => $state === self::OTRO ? null : $state),

            TextInput::make('titulo_otro')
            ->label('Escriba el título')
            ->helperText('Use esta opción solo si el título no está en la lista.')
            ->maxLength(100)
            ->visible(fn (Get $get) => $get('titulo_id') === self::OTRO)
            ->required(fn (Get $get) => $get('titulo_id') === self::OTRO)
            ->dehydratedWhenHidden()
            ->dehydrateStateUsing(fn ($state, Get $get) => $get('titulo_id') === self::OTRO ? Str::squish((string) $state) : null),
        ];
    }
}