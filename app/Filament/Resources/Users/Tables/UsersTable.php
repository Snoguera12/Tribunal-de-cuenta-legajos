<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\Auditoria;
use App\Models\User;
use App\Services\ControlIntentosLogin;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('loginIntento'))
            ->columns([
                ...User::getOutSchema('table'),

                TextColumn::make('bloqueo')
                    ->label('Bloqueo')
                    ->state(fn (User $record) => match (ControlIntentosLogin::estadoDe($record)) {
                        'permanente' => 'Definitivo',
                        'temporal' => 'Temporal',
                        default => 'No',
                    })
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Definitivo' => 'danger',
                        'Temporal' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('debe_cambiar_clave')
                    ->label('Clave provisoria')
                    ->formatStateUsing(fn ($state) => $state ? 'Sí' : 'No')
                    ->badge()
                    ->color(fn ($state) => $state ? 'warning' : 'gray')
                    ->toggleable(),

                TextColumn::make('sesion_activa_desde')
                    ->label('Sesión abierta desde')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('No tiene')
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Editar')
                    ->iconButton()
                    ->color('primary')
                    ->icon('heroicon-m-pencil-square'),

                // solo el administrador da permiso otra vez
                Action::make('desbloquear')
                    ->label('Desbloquear')
                    ->icon('heroicon-m-lock-open')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (User $record) => auth()->user()?->isAdmin() && ControlIntentosLogin::estadoDe($record) !== null)
                    ->action(function (User $record) {
                        abort_unless(auth()->user()?->isAdmin(), 403);

                        ControlIntentosLogin::desbloquear($record, auth()->user());

                        Notification::make()->title('Usuario desbloqueado')->success()->send();
                    }),

                Action::make('cerrar_sesion')
                    ->label('Cerrar su sesión')
                    ->icon('heroicon-m-arrow-right-start-on-rectangle')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (User $record) => auth()->user()?->isAdmin() && $record->sesion_activa_id !== null)
                    ->action(function (User $record) {
                        abort_unless(auth()->user()?->isAdmin(), 403);

                        $record->cerrarSesiones();

                        Auditoria::registrar(
                            evento: 'sesion_cerrada_por_admin',
                            descripcion: "El administrador cerró la sesión de {$record->name}",
                            modelo: $record,
                        );

                        Notification::make()->title('Sesión cerrada')->success()->send();
                    }),
            ])
            ->toolbarActions([
            ]);
    }
}
