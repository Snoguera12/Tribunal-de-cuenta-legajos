<?php

namespace App\Filament\Resources\Estudios\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EstudiosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                /*TextColumn::make('nombre')
                    ->label('Nombre del Estudio')
                    ->sortable()
                    ->searchable(),*/
                TextColumn::make('institucion')
                    ->label('Institución')
                    ->sortable()
                    ->searchable(),
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
                ),
                TextColumn::make('fecha_inicio')
                    ->label('Fecha de Inicialización')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('fecha_fin')
                    ->label('Fecha de Finalización')
                    ->date('d/m/Y')
                    ->sortable(),
                IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: false),
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
