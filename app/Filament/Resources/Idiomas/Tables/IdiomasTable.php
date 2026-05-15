<?php

namespace App\Filament\Resources\Idiomas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class IdiomasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('persona.nombre')
                    ->label('Nombre')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('persona.apellido')
                    ->label('Apellido')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('nombre')
                    ->label('Idioma')
                    ->searchable(),
                TextColumn::make('nivel')
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
