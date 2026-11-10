<?php

namespace App\Console\Commands;

use App\Filament\Resources\Legajos\LegajoResource;
use App\Filament\Resources\Tokens\TokenResource;
use App\Models\Legajo;
use App\Models\Token;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Recordatorios diarios en la campanita del panel para administrador y RRHH:
 *  - legajos sin datos completos
 *  - tokens de API próximos a vencer
 * No repite el mismo aviso todos los días (ver cada método).
 */
class RecordatoriosEnviar extends Command
{
    protected $signature = 'recordatorios:enviar {--dias-token=7 : avisar cuando falten estos días o menos para que venza un token}';

    protected $description = 'Envía a administrador y RRHH los recordatorios del día (legajos sin datos, tokens por vencer)';

    // si la cantidad de legajos sin datos no sube, se vuelve a recordar recién pasados estos días
    private const REPETIR_SIN_DATOS_DIAS = 7;

    public function handle(): int
    {
        $destinatarios = User::whereIn('rol', ['administrador', 'rrhh'])->get();

        if ($destinatarios->isEmpty()) {
            $this->warn('No hay usuarios administrador o RRHH a quienes avisar.');

            return self::SUCCESS;
        }

        $this->legajosSinDatos($destinatarios);
        $this->tokensPorVencer($destinatarios, (int) $this->option('dias-token'));

        return self::SUCCESS;
    }

    // Avisa si la cantidad sube, o una vez por semana mientras siga habiendo. Mismo criterio que el botón "Legajos sin datos".
    private function legajosSinDatos(Collection $destinatarios): void
    {
        $clave = 'recordatorios.legajos_sin_datos';
        $cantidad = Legajo::sinDatos()->count();

        if ($cantidad === 0) {
            Cache::forget($clave);

            return;
        }

        $ultimo = Cache::get($clave);
        $hayQueAvisar = ! $ultimo
            || $cantidad > $ultimo['cantidad']
            || Carbon::parse($ultimo['fecha'])->diffInDays(now()) >= self::REPETIR_SIN_DATOS_DIAS;

        if (! $hayQueAvisar) {
            return;
        }

        Notification::make()
            ->title($cantidad === 1 ? '1 legajo sin datos completos' : "{$cantidad} legajos sin datos completos")
            ->body('Falta área, categoría, cargo o persona vinculada.')
            ->warning()
            ->actions([
                Action::make('ver')
                    ->label('Ver legajos')
                    ->button()
                    ->markAsRead()
                    ->url(LegajoResource::getUrl('index', [
                        'filters' => ['sin_datos' => ['isActive' => true], 'estado' => ['value' => '']],
                    ], panel: 'legajos')),
            ])
            ->sendToDatabase($destinatarios);

        Cache::forever($clave, ['cantidad' => $cantidad, 'fecha' => now()->toDateTimeString()]);
        $this->info("Aviso enviado: {$cantidad} legajos sin datos.");
    }

    // Un aviso por token y por fecha de vencimiento (si se renueva el token, vuelve a avisar).
    private function tokensPorVencer(Collection $destinatarios, int $dias): void
    {
        $limite = now()->addDays($dias);

        foreach (Token::all() as $token) {
            $vence = $token->tokens()->where('expires_at', '>', now())->max('expires_at');

            if (! $vence || Carbon::parse($vence)->greaterThan($limite)) {
                continue;
            }

            $vence = Carbon::parse($vence);
            $clave = "recordatorios.token.{$token->id}.{$vence->format('YmdHi')}";

            if (Cache::has($clave)) {
                continue;
            }

            Notification::make()
                ->title("El token «{$token->nombre}» vence pronto")
                ->body('Vence el ' . $vence->format('d/m/Y H:i') . '. Después de esa fecha el sistema que lo usa dejará de conectarse.')
                ->warning()
                ->actions([
                    Action::make('ver')
                        ->label('Ver tokens')
                        ->button()
                        ->markAsRead()
                        ->url(TokenResource::getUrl('index', panel: 'legajos')),
                ])
                ->sendToDatabase($destinatarios);

            Cache::put($clave, true, now()->addDays($dias + 1));
            $this->info("Aviso enviado: token {$token->nombre} vence {$vence->format('d/m/Y')}.");
        }
    }
}
