<?php

namespace App\Models;

use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;


class ApiClient extends Model
{
    use HasApiTokens;

    protected $fillable = [
        'nombre',
        'is_active',
        'is_token'
    ];
    public static function getFuncEliminarToken(ApiClient $record) : array {
        $resultado = [
            $record->tokens()->delete(),

            $record->forceDelete(),

            Notification::make()
            ->title('Token borrado con éxito')
            ->success()
            ->send()
        ];

        return $resultado;
    }
}
