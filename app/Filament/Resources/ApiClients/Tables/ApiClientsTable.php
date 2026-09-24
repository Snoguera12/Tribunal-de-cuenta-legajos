<?php

namespace App\Filament\Resources\ApiClients\Tables;

use App\Models\ApiClient;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Hamcrest\Core\Set;
use Illuminate\Support\HtmlString;

class ApiClientsTable
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
                // El interruptor solo se puede usar si ya se generó un token
                ->disabled(fn (ApiClient $record): bool => !$record->is_token)
                // Opcional: Ejecuta código extra justo después de que el usuario cambie el switch
                ->afterStateUpdated(function ($state) {
                    Notification::make() // O la notificación estándar
                    ->title($state ? 'Cliente activado' : 'Cliente desactivado')
                    ->success()
                    ->send();
                }),


                TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->actions([
            EditAction::make(),
            
            // Acción personalizada para generar el token
            Action::make('generateToken')
            ->label('Generar Token')
            ->icon('heroicon-o-key')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('¿Generar nuevo API Token?')
            ->modalDescription('Este cliente se activará automáticamente y este nuevo token solo se mostrará UNA VEZ.')
            
            // Condición: El botón se oculta de la interfaz si el cliente ya está activo
            ->hidden(fn (ApiClient $record): bool => $record->is_token)
            ->action(function (ApiClient $record) {
                // 1. Activamos el cliente en la base de datos
                $record->update([
                    'is_active' => true,
                    'is_token' => true,
                ]);

                // 2. Generamos el token usando Sanctum
                $tokenName = 'Token-' . $record->name . '-' . now()->format('YmdHis');
                $token = $record->createToken($tokenName)->plainTextToken;

                // 3. Enviamos la notificación persistente con el token
                Notification::make()
                ->title('Token generado y Cliente activado')
                ->body(new HtmlString("
                    <p class='mb-2 text-sm text-gray-600 dark:text-gray-400'>Copia este token ahora. No volverá a mostrarse por seguridad:</p>
                    <div class='flex items-center gap-2 p-2 bg-gray-100 rounded dark:bg-gray-800'>
                        <code class='flex-1 font-mono text-xs select-all text-danger-600 dark:text-danger-400'>{$token}</code>
                    </div>
                "))
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
            ->action(function (ApiClient $record) {
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
