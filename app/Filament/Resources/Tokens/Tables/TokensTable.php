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
use Illuminate\Support\HtmlString;

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
                Action::make('generateToken')
                    ->label('Generar Token')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('¿Generar nuevo API Token?')
                    ->modalDescription('El token se activará automáticamente y solo se mostrará UNA VEZ.')
                    ->hidden(fn (Token $record): bool => $record->is_created) // Condición: El botón se oculta de la interfaz si el cliente ya está activo
                    ->schema([
                        Select::make('duracion')
                        ->label('Duración')
                        ->options(TokenDuracion::class)
                        ->required(),
                    ])
                    ->hidden(fn (Token $record): bool => $record->tokens()
                    ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                    ->exists())
                    ->action(function (Token $record, array $data) {
                        $duracion = $data['duracion'] instanceof TokenDuracion
                        ? $data['duracion']
                        : TokenDuracion::from($data['duracion']);

                        // Se calcula UNA sola vez y se usa en todo
                        $expiraEn = $duracion->expiraEn()->startOfSecond();

                        $record->update([
                            'is_active' => true,
                            'is_created' => true,
                        ]);

                        $tokenName = 'Token-' . $record->nombre . '-' . now()->format('YmdHis');
                        $token = $record->createToken($tokenName, ['*'], $expiraEn)->plainTextToken;

                        $record->anotarGeneracion($expiraEn);

                        // Programa el borrado para el momento del vencimiento
                        EliminarTokenVencido::dispatch()->delay($expiraEn->copy()->addSeconds(2));
                        
                        // 3. Enviamos la notificación persistente con el token
                        Notification::make()
                        ->title('Token hacido generado')
                        ->body(new HtmlString("
                            <p class='mb-2 text-sm text-gray-600 dark:text-gray-400'>Copia este token ahora. No volverá a mostrarse por seguridad:</p>
                            <div class='flex items-center gap-2 p-2 bg-gray-100 rounded dark:bg-gray-800'>
                                <code class='flex-1 font-mono text-xs select-all text-danger-600 dark:text-danger-400'>{$token}</code>
                            </div>
                        ")
                    )
                ->success()
                ->persistent()
                ->send();
                }),
                
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
                    ->body('No se pudo enviar al destino, así que no se creó ningún token. ' . $resultado['mensaje'])
                    ->danger()
                    ->persistent()
                    ->send();
                }),

                Action::make('deleteToken')
                ->label('Revocar acceso')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
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

    /**
     * Activa el cliente, crea el token de Sanctum y programa su borrado al vencer.
     * @return array{texto: string, vence: \Illuminate\Support\Carbon}
     */
    private static function generarToken(Token $record, TokenDuracion|string $duracion): array
    {
        $duracion = $duracion instanceof TokenDuracion ? $duracion : TokenDuracion::from($duracion);

        // Se calcula UNA sola vez y se usa en todo
        $expiraEn = $duracion->expiraEn()->startOfSecond();

        $record->update([
            'is_active' => true,
            'is_created' => true,
            'enviados' => null,
        ]);

        $tokenName = 'Token-' . $record->nombre . '-' . now()->format('YmdHis');
        $texto = $record->createToken($tokenName, ['*'], $expiraEn)->plainTextToken;

        // Programa el borrado para el momento del vencimiento
        EliminarTokenVencido::dispatch()->delay($expiraEn->copy()->addSeconds(2));

        return ['texto' => $texto, 'vence' => $expiraEn];
    }

    private static function cajaDelToken(string $texto): string
    {
        return "
            <p class='mb-2 text-sm text-gray-600 dark:text-gray-400'>Copia este token ahora. No volverá a mostrarse por seguridad:</p>
            <div class='flex items-center gap-2 p-2 bg-gray-100 rounded dark:bg-gray-800'>
                <code class='flex-1 font-mono text-xs select-all text-danger-600 dark:text-danger-400'>{$texto}</code>
            </div>
        ";
    }
}
