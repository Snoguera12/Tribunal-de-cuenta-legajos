<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label("Nombre de Usuario")
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Correo Electrónico')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email_verified_at')
                    ->label('Correo Verificado')
                    ->dateTime('d/m/Y H:i:s')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('rol')
                    ->label('Rol')
                    ->formatStateUsing(fn (int $state): string => match ($state) {
                    0 => 'Genérico',
                    1 => 'Funcionario',
                    2 => 'RRHH', 
                    3 => 'Empleado',
                    4 => 'Administrador',
                    default => 'Desconocido',
                }),  // 'Genérico', 'Funcionario', 'RRHH', 'Empleado', 'Administrador'
                TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime('d/m/Y H:i:s')
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
            ]);
    }
}
