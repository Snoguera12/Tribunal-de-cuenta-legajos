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

class TokensTable
{
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

                TextColumn::make('enviado_at')
                ->label('Enviado a licencias')
                ->dateTime()
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
                ->label('Generar y enviar a licencias')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible(fn (): bool => auth()->user()?->isAdmin_RRHH() ?? false)
                ->requiresConfirmation()
                ->modalHeading('¿Generar el token y enviarlo a licencias?')
                ->modalDescription('El token se envía por HTTPS al buzón del sistema de licencias. Si el envío falla, el token se muestra acá una sola vez para que no se pierda.')
                ->hidden(fn (Token $record): bool => $record->is_created)
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
                    $envio = BuzonLicencias::enviar($record, $generado['texto'], $generado['vence']);

                    if ($envio['ok']) {
                        Notification::make()
                        ->title('Token generado y enviado a licencias')
                        ->body($envio['mensaje'])
                        ->success()
                        ->send();

                        return;
                    }

                    // Si el envío falla, el token se muestra una sola vez para no perderlo
                    Notification::make()
                    ->title('Token generado, pero no se pudo enviar')
                    ->body(new HtmlString(
                        "<p class='mb-2 text-sm'>{$envio['mensaje']}</p>" . self::cajaDelToken($generado['texto'])
                    ))
                    ->warning()
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
            'enviado_at' => null,
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
