<?php

namespace App\Filament\Resources\Estudios\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EstudiosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('persona.nombre')
                    ->label('Nombre')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('persona.apellido')
                    ->label('Apellido')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('persona.dni')
                    ->label('DNI')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
                /*TextColumn::make('nombre')
                    ->label('Nombre del Estudio')
                    ->sortable()
                    ->searchable(),*/
                TextColumn::make('institucion')
                    ->label('Institución')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('nivel_estudio')
                    ->label('Nivel de Estudio')
                    ->formatStateUsing(fn (int $state): string => match ($state) {
                        1 => 'Primaria',
                        2 => 'Secundaria',
                        3 => 'Terciario',
                        4 => 'Universitario',
                        5 => 'Doctorado',
                        6 => 'Maestría',
                        default => 'Desconocido',
                    }
                )
                ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('fecha_fin')
                    ->label('Fecha de Finalización')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('created_at')
                    ->label('Fecha de subida')
                    ->datetime('d/m/Y H:i:s')
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
