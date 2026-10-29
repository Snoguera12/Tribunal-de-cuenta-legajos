<?php

namespace App\Models;

use Filament\Notifications\Notification;
use App\Models\Concerns\RegistraAuditoria;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;


// implementa Authenticatable porque el limite de pedidos por minuto (throttle) necesita getAuthIdentifier()
class ApiClient extends Model implements AuthenticatableContract
{
    // permisos que se le pueden dar a un token (los tokens viejos tienen "*" y siguen pudiendo todo)
    public const HABILIDADES = [
        'personas:leer' => 'Consultar el listado de personas',
        'legajos:leer' => 'Consultar un legajo por DNI',
        'licencias:crear' => 'Registrar licencias y enviar el PDF de la licencia aprobada',
    ];

    // solo permisos que existen (null = todos); sirve para armar el token desde la pantalla de tokens
    public static function habilidadesValidas(?array $pedidas): array
    {
        return array_values(array_intersect(array_keys(self::HABILIDADES), $pedidas ?? array_keys(self::HABILIDADES)));
    }

    use Authenticatable;
    use RegistraAuditoria;

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
