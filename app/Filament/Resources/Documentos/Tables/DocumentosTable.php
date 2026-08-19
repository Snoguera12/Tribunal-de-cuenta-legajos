<?php

namespace App\Filament\Resources\Documentos\Tables;

use Filament\Actions\EditAction;
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
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('archivo')
                ->label('Documento')
                ->formatStateUsing(fn () => 'Descargar archivo')
                ->url(fn ($record): string => route('documentos.descargar', $record)) // antes usaba Storage::url(), ahora va por la ruta protegida
                ->openUrlInNewTab()
                ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('tipodoc')
                ->label('Tipo de Documento')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                //
            ]);
    }
}
