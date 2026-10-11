<?php

namespace App\Models;

use DB;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

class Titulo extends Model
{
    protected $fillable = [
        'nombre',
    ];
    public function estudioTitulos() {
        return $this->hasMany(EstudioTitulo::class);
    }
    public function estudios() {
        return $this->belongsToMany(Estudio::class, 'estudio_titulo')->withTimestamps();
    }

    public static function getOpcionesNombre(): array {
        static $opciones = null;
        return $opciones ??= static::query()
        ->whereNotNull('nombre')
        ->where('nombre', '!=', 'otro')
        ->distinct()
        ->orderBy('nombre')
        ->pluck('nombre')
        ->mapWithKeys(fn (string $nombre): array => [$nombre => $nombre])
        ->all();
    }

    public static function getFormSchema(string $modeForm): array{
        $resultado = match($modeForm){
            'table' => [
                TextInput::make('nombre')
                ->label('Nombre del Título')
                ->required(),
            ],
            'list' => [
                Select::make('nombre')
                ->label('Nombre del Título')
                ->required()
                ->searchable()
                ->native(false)
                ->live()
                ->options(
                    fn (): array => [static::OTRO => 'Otro (Especificar)'] + static::getOpcionesNombre()
                ),
                /*->dehydrateStateUsing(fn (?string $state, Get $get): ?string => $state === static::OTRO 
                    ? (trim((string) $get('nombre_otro')) ?: null)
                    : $state
                )*/

                TextInput::make('nombre_otro')
                ->label('Introduccir el Nombre del Título')
                ->required()
                ->visible(fn (GET $get): bool => $get('nombre') === static::OTRO)
                ->validationMessages([
                    "required" => "Requiere introducir el Nombre del Título.",
                ])
                ->extraInputAttributes([
                    'oninvalid' => "this.setCustomValidity('Requiere introducir el Nombre del Título.')",
                    'oninput' => "this.setCustomValidity('')",
                ]),
            ],
        };

        return $resultado;
    }
    public static function getOutSchema(string $mode_out) : array{
        $resultado = match ($mode_out) {
            'table' => [
                TextColumn::make('nombre')
                ->label('Nombre del Título')
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('estudioTitulos.titulo_otro')
                ->label('Especificó un Nombre')
                ->placeholder('-')
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: false),

                /*TextColumn::make('estudio_titulos_count')
                ->counts('estudioTitulos')
                ->label('Usos')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: false),*/

                TextColumn::make('created_at')
                ->label('Fecha de Creación')
                ->dateTime('d/m/Y H:i:s')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            ],
            'list' => [
                TextEntry::make('nombre')
                ->hiddenLabel()
                ->visible(fn ($record) => $record->nombre != 'otro')
                ->icon('heroicon-m-academic-cap'),
                
                TextEntry::make('nombre_otro')
                ->hiddenLabel()
                ->visible(fn ($record) => $record->nombre == 'otro')
                ->icon('heroicon-m-academic-cap'),
            ],
        };
        return $resultado;
    }
    public function fusionarEn(Titulo $destino): void
    {
        DB::transaction(function () use ($destino) {
            $this->estudioTitulos()->get()->each(function (EstudioTitulo $et) use ($destino) {
                EstudioTitulo::where(
                    'estudio_id', $et->estudio_id)->where('titulo_id', $destino->id)->exists()
                    ? $et->delete()
                    : $et->update(['titulo_id' => $destino->id]);
            });

            $this->delete();
        });
    }
}
