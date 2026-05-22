<?php

namespace App\Filament\Resources\Historialbajas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HistorialbajasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('legajo.num_legajo')
                    ->label("Número de legajo")
                    ->sortable(),
                TextColumn::make('motivo')
                    ->label('Motivo de la baja')
                        ->formatStateUsing(fn (int $state): string => match ($state) {
                        0 => 'Renuncia',
                        1 => 'Despido',
                        2 => 'Vencimiento de Contrato',
                        3 => 'Jubilación',
                        4 => 'Fallecimiento',
                        5 => 'Incapacidad',
                        6 => 'Traslado',
                        default => 'Desconocido',
                    }),
                TextColumn::make('fecha_baja')
                    ->dateTime()
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
