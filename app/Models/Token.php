<?php
namespace App\Models;

use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;

class Token extends Model
{
    use HasApiTokens;
    // un cliente de la API nunca se borra: se revoca su acceso (queda el registro y su historia en la auditoría)
    use \App\Models\Concerns\NoSeBorra;
    use \App\Models\Concerns\RegistraAuditoria;

    protected $fillable = [
        'nombre',
        'is_active',
        'is_created',
        'enviados',
    ];
    protected $casts = ['expires_at' => 'datetime', 'enviados' => 'array'];
    public function restablecer(): void
    {
        $this->update([
            'is_active' => false,
            'is_created' => false,
        ]);
    }


    // corta el acceso ya: borra las credenciales de Sanctum (son secretos, no datos) y deja el cliente inactivo.
    // El registro del cliente se conserva y se le puede generar un token nuevo
    public function revocar(): int
    {
        $credenciales = $this->tokens()->count();

        $this->tokens()->delete();
        $this->update(['is_active' => false, 'is_created' => false, 'enviados' => null]);

        Auditoria::registrar(
            evento: 'token_revocado',
            descripcion: "Se revocó el acceso de la API a {$this->nombre}",
            modelo: $this,
            extra: ['credenciales_borradas' => $credenciales],
        );

        return $credenciales;
    }


    // deja constancia de que se generó un token (nunca el texto del token)
    public function anotarGeneracion(\DateTimeInterface $vence, ?string $destino = null): void
    {
        Auditoria::registrar(
            evento: 'token_generado',
            descripcion: "Se generó un token de la API para {$this->nombre}" . ($destino ? " y se envió a {$destino}" : ''),
            modelo: $this,
            extra: ['vence' => $vence->format('Y-m-d H:i:s'), 'destino' => $destino],
        );
    }


    public static function tablaColumnas() : array {
        $resultado = [
            TextColumn::make('nombre')
            ->label("Nombre")
            ->searchable(),

            ToggleColumn::make('is_active')
            ->label('Estado')
            ->disabled(fn (Token $record): bool => !$record->is_created)// El interruptor solo se puede usar si ya se generó un token
            ->afterStateUpdated(function ($state) {
                Notification::make() // O la notificación estándar
                ->title($state ? 'Cliente activado' : 'Cliente desactivado')
                ->success()
                ->send();

            }),

            TextColumn::make('vence')
            ->label('Fecha de Vencimiento')
            ->state(fn (Token $record) => $record->tokens()->max('expires_at'))
            ->placeholder('Sin token')
            ->dateTime(),

            TextColumn::make('enviados')
            ->label('Enviado a')
            ->state(fn (Token $record) => collect($record->enviados ?? [])
                ->map(fn ($fecha, $destino) => (config("buzones.{$destino}.nombre") ?? $destino)
                    . ' (' . \Illuminate\Support\Carbon::parse($fecha)->format('d/m H:i') . ')')
                ->implode(', ') ?: null)
            ->placeholder('No enviado'),

            TextColumn::make('created_at')
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('updated_at')
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true),
        ];
        return $resultado;
    }
    
    public static function purgarVencidos(): int
    {
        return DB::transaction(function (): int {
            $vencidos = PersonalAccessToken::query()
                ->where('tokenable_type', (new static)->getMorphClass())
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now())
                ->get(['id', 'tokenable_id']);

            if ($vencidos->isEmpty()) {
                return 0;
            }

            PersonalAccessToken::whereIn('id', $vencidos->pluck('id'))->delete();

            static::query()
                ->whereIn('id', $vencidos->pluck('tokenable_id')->unique())
                ->doesntHave('tokens')
                ->get()
                ->each->restablecer();

            return $vencidos->count();
        });
    }
}
