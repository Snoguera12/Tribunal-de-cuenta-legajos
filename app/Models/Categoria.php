<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $fillable = [
        "nombre",
        "descripcion",
    ];
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
}
