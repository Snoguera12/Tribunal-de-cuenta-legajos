<?php

namespace App\Filament\Resources\Tokens\Tables;

use App\Enums\TokenDuracion;
use App\Jobs\EliminarTokenVencido;
use App\Models\Token;
use App\Services\BuzonLicencias;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class TokensTable
{
    /**
     * Crea el token y lo envía al destino dentro de una transacción.
     * Si el envío falla, se deshace todo: no queda ningún token creado.
     * @return array{ok: bool, mensaje: string}
     */
    private static function generarYEnviar(Token $record, TokenDuracion|string $duracion, string $destino): array
    {
        $duracion = $duracion instanceof TokenDuracion ? $duracion : TokenDuracion::from($duracion);
        $expiraEn = $duracion->expiraEn()->startOfSecond();

        DB::beginTransaction();

        try {
            // Un solo token vivo por cliente y por destino. Se controla acá y no solo ocultando el botón.
            // Primero se bloquean los clientes (son pocos): dos envíos a la vez se hacen en fila y el segundo ve al primero.
            Token::query()->lockForUpdate()->pluck('id');

            if ($record->tokens()->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->exists()) {
                DB::rollBack();

                return ['ok' => false, 'mensaje' => 'Este cliente ya tiene un token vigente. Revoca su acceso antes de generar otro.'];
            }

            if ($ocupante = Token::ocupanteDelDestino($destino, $record->id)) {
                DB::rollBack();

                $nombreDestino = config("buzones.{$destino}.nombre") ?? $destino;

                return ['ok' => false, 'mensaje' => "{$nombreDestino} ya tiene un token vigente (cliente «{$ocupante->nombre}»). Revoca ese acceso antes de enviar otro."];
            }

            $record->update([
                'is_active' => true,
                'is_created' => true,
                'enviados' => null,
            ]);

            $tokenName = 'Token-' . $record->nombre . '-' . now()->format('YmdHis');
            $texto = $record->createToken($tokenName, ['*'], $expiraEn)->plainTextToken;

            $envio = BuzonLicencias::enviar($destino, $record, $texto, $expiraEn);

            if (! $envio['ok']) {
                DB::rollBack();

                return ['ok' => false, 'mensaje' => $envio['mensaje']];
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        $record->anotarGeneracion($expiraEn, $destino);

        // Programa el borrado para el momento del vencimiento (solo si el token quedó creado)
        EliminarTokenVencido::dispatch()->delay($expiraEn->copy()->addSeconds(2));

        return ['ok' => true, 'mensaje' => $envio['mensaje']];
    }
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(Token::tablaColumnas())
            ->recordActions([ EditAction::make(), ])
            ->actions([
                // Genera el token y lo envía de una vez al buzón del sistema de licencias.
                // El texto del token existe solo en este momento, por eso el envío va en la misma acción.
                Action::make('generarYEnviar')
                ->label('Generar y Enviar a...')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible(fn (): bool => (auth()->user()?->isAdmin_RRHH() ?? false) && BuzonLicencias::destinos() !== [])
                ->requiresConfirmation()
                ->modalHeading('¿Generar el token y enviarlo?')
                ->modalDescription('El token se envía por HTTPS al destino elegido. Si el envío falla, no se crea ningún token.')
                ->hidden(fn (Token $record): bool => $record->tokens()
                    ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                    ->exists())
                ->schema([
                    Select::make('destino')
                    ->label('Destino')
                    ->options(fn () => BuzonLicencias::destinos())
                    ->default(fn () => array_key_first(BuzonLicencias::destinos()))
                    ->required(),

                    Select::make('duracion')
                    ->label('Duración')
                    ->options(TokenDuracion::class)
                    ->required(),
                ])
                ->action(function (Token $record, array $data) {
                    $resultado = self::generarYEnviar($record, $data['duracion'], $data['destino']);

                    if ($resultado['ok']) {
                        Notification::make()
                        ->title('Token generado y enviado')
                        ->body($resultado['mensaje'])
                        ->success()
                        ->send();

                        return;
                    }

                    Notification::make()
                    ->title('No se generó el token')
                    ->body($resultado['mensaje'] . ' No se creó ningún token.')
                    ->danger()
                    ->persistent()
                    ->send();
                }),

                Action::make('deleteToken')
                ->label('Revocar acceso')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->visible(fn (Token $record): bool => $record->tokens()
                ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->exists())
                ->requiresConfirmation()
                ->modalHeading('¿Revocar el acceso de este cliente?')
                ->modalDescription('El token deja de funcionar de inmediato y el cliente queda inactivo. El registro del cliente se conserva y se puede generar un token nuevo.')
                ->action(function (Token $record) {
                    $record->revocar();

                    Notification::make()
                    ->title('Acceso revocado')
                    ->success()
                    ->send();
                }),
        ]);
    }
}
