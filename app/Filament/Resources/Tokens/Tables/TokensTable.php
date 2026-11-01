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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
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

        // Programa el borrado para el momento del vencimiento (solo si el token quedó creado)
        EliminarTokenVencido::dispatch()->delay($expiraEn->copy()->addSeconds(2));

        return ['ok' => true, 'mensaje' => $envio['mensaje']];
    }
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
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
            ])
            ->recordActions([ EditAction::make(), ])
            ->actions([
                // Acción personalizada para generar el token
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
                    $generado = self::generarToken($record, $data['duracion']);

                    // 3. Enviamos la notificación persistente con el token
                    Notification::make()
                    ->title('Token hacido generado')
                    ->body(new HtmlString(self::cajaDelToken($generado['texto'])))
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
                ->modalDescription('El token se envía por HTTPS al destino elegido. Si el envío falla, el token se muestra acá una sola vez para que no se pierda.')
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
            ->label('Borrar Token')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('¿Borrar / Revocar Token?')
            ->modalDescription('Esto eliminará de forma permanente cualquier token de Sanctum asociado. El cliente se desactivará de inmediato.')
            ->action(function (Token $record) {
                $record->tokens()->delete();

                $record->forceDelete();

                Notification::make()
                ->title('Token borrado con éxito')
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
