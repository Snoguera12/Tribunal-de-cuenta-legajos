<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use App\Services\ControlIntentosLogin;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('loginIntento'))
            ->columns([
                ...User::getOutSchema('table'),

                IconColumn::make('habilitado')
                    ->label('Habilitado')
                    ->boolean(),

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
            ])
            ->filters([
                TernaryFilter::make('habilitado')
                    ->label('Habilitado')
                    ->trueLabel('Solo habilitados')
                    ->falseLabel('Solo deshabilitados'),
            ])
            ->actions([
                
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
            ])
            ->toolbarActions([
                /*BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),*/
            ]);
    }
}
