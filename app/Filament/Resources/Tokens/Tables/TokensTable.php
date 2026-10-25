<?php

namespace App\Filament\Resources\Tokens\Tables;

use App\Enums\TokenDuracion;
use App\Models\Token;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use App\Jobs\EliminarTokenVencido;

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
}
