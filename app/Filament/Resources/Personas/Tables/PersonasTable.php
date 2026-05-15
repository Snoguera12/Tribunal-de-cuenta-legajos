<?php

namespace App\Filament\Resources\Personas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PersonasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->searchable(),
                TextColumn::make('apellido')
                    ->searchable(),
                TextColumn::make('dni')
                    ->label("DNI")
                    ->searchable(),
                TextColumn::make('cuil')
                    ->label("CUIL")
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('sexo')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                    '0' => 'Femenino',
                    '1' => 'Masculino',
                    default => 'Desconocido',
                })
                ->searchable(),
                TextColumn::make('fecha_de_nacimiento')
                    ->date()
                    ->sortable(),
                TextColumn::make('domicilio')
                    ->searchable(),
                TextColumn::make('telefono')
                    ->label("Teléfono")
                    ->searchable(),
                TextColumn::make('telefono_emergencia')
                    ->label("Teléfono de emergencia")
                    ->searchable(),
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
            ]);
    }
}
