<?php

namespace App\Filament\Resources\Documentos\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('legajo.num_legajo')
                    ->label("Número de legajo")
                    ->sortable(),
                /*TextColumn::make('archivo')
                    ->searchable(),*/
                TextColumn::make('tipodoc')
                    ->label('Tipo de Docimento')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('activo')
                    ->boolean(),
                TextColumn::make('fecha_de_creacion')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                TextColumn::make('fecha_de_subida')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
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
