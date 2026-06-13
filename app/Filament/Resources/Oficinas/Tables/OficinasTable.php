<?php

namespace App\Filament\Resources\Oficinas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OficinasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->label('Nombre de la Oficina')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('area.nombre')
                    ->label("Área")
                    ->sortable(),
                TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->wrap() // Hace que el texto salte de línea si es largo
                    ->lineClamp(2), // (Opcional) Recorta el texto a 2 líneas y añade "..."
                TextColumn::make('fecha_creacion')
                    ->dateTime('d/m/Y H:i:s')
                    ->label('Fecha de creación')
                    ->wrap() // Hace que el texto salte de línea si es largo
                    ->lineClamp(2), // (Opcional) Recorta el texto a 2 líneas y añade "..."
                
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
