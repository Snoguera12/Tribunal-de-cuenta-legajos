<?php

namespace App\Models;

use App\Enums\IdiomaNivelEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
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

    public static function getRepeaterSchema(): array{
        return [
            Repeater::make('idiomas')
            ->relationship('idiomas')
            ->hiddenLabel()
            ->columnSpanFull()
            ->addActionLabel('Agregar un Idioma')
            ->itemLabel('Idioma Adjuntado')
            ->defaultItems(0)
            ->collapsible()
            ->grid(3)
            ->addAction(
                fn (Action $action) => $action
                ->icon('heroicon-m-plus')
                ->color('gray')
            )
            ->schema([
                Grid::make(1)
                ->columnSpan(1)
                ->schema(Idioma::getFromSchema()),
            ]),
        ];
    }
}
