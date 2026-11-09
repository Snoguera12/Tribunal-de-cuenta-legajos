<?php

namespace App\Models;

use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Idioma extends Model
{
    protected $fillable = [
        'nombre',
    ];

    public function personaIdiomas()
    {
        return $this->hasMany(PersonaIdioma::class);
    }

    public function fusionarEn(Idioma $destino): void
    {
        DB::transaction(function () use ($destino) {
            $this->personaIdiomas()->get()->each(function (PersonaIdioma $pi) use ($destino) {
                $yaLoTiene = PersonaIdioma::where('persona_id', $pi->persona_id)
                ->where('idioma_id', $destino->id)
                ->exists();

                // Si la persona ya tenía el idioma destino, el duplicado se desadjunta en vez de borrarse.
                $pi->update($yaLoTiene
                    ? ['persona_id' => null, 'idioma_id' => $destino->id]
                    : ['idioma_id' => $destino->id]);
            });

            $this->delete();
        });
    }

    public static function getFormSchema(string $modeForm): array
    {
        return match ($modeForm) {
            'table' => [
                TextInput::make('nombre')
                    ->label('Nombre del Idioma')
                    ->maxLength(100)
                    ->unique(ignoreRecord: true)
                    ->required(),
            ],
            'list' => [],
        };
    }

    public static function getOutSchema(string $mode_out): array
    {
        return match ($mode_out) {
            'table' => [
                TextColumn::make('nombre')
                    ->label('Nombre del Idioma')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ],
            'list' => [],
        };
    }
}