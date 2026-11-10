<?php

namespace App\Models;

use App\Models\Concerns\NoSeBorra;
use App\Filament\Tables\ConteoLegajos;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use NoSeBorra;

    protected $fillable = [
        "nombre",
        "descripcion",
    ];

    public function legajos()
    {
        return $this->hasMany(Legajo::class);
    }

    public static function getNombre(){
        return fn ($record) => $record->categoria ? "{$record->categoria->nombre} {$record->categoria->descripcion}" : 'Sin asignar';
    }

    public static function getCategorias(): array
    {
        return self::query()
        ->get(['id', 'nombre', 'descripcion'])
        ->mapWithKeys(fn ($item) => [
            $item->id => "{$item->nombre} {$item->descripcion}"
        ])
        ->toArray();
    }

    public static function getFormSchema(): array
    {
        return [
            TextInput::make('nombre')
            ->label('Nombre de la Categoría')
            ->required()
            ->maxLength(10)
            ->unique(ignoreRecord: true),

            TextInput::make('descripcion')
            ->label('Descripción')
            ->required()
            ->maxLength(50),
        ];
    }

    public static function getOutSchema(): array
    {
        return [
            TextColumn::make('nombre')
            ->label('Nombre de la Categoría')
            ->sortable()
            ->searchable(),

            TextColumn::make('descripcion')
            ->label('Descripción')
            ->sortable()
            ->searchable(),

            ConteoLegajos::columna(),
        ];
    }
}