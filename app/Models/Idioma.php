<?php

namespace App\Models;

use App\Enums\IdiomaNivelEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\Model;

class Idioma extends Model
{
    protected $casts = [
        'nivel' => IdiomaNivelEnum::class,
    ];
    protected $fillable = [
        'persona_id',
        'idioma',
        'nivel',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }
    public static function getFromSchema() : array{
        $resultado =  [
            TextInput::make('idioma')
            ->label('Idioma')
            ->required()
            ->maxLength(100),

            Select::make('nivel')
            ->label('Nivel')
            ->options(IdiomaNivelEnum::class)
            ->required(),
        ];
        return $resultado;
    }
    public static function getOutSchema(string $mode_out) : array{
        $resultado = match ($mode_out) {
            'table' => [

            ],
            'list' => [
                TextEntry::make('idioma')->label('Idioma'),
                TextEntry::make('nivel')->label('Nivel'),
            ],
        };

        return $resultado;
    }
}
