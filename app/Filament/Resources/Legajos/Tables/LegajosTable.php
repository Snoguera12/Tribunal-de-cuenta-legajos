<?php

namespace App\Filament\Resources\Legajos\Tables;

use App\Models\Cargo;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use PhpParser\Node\Stmt\Label;

class LegajosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('número de legajo')
                    ->searchable(),
                TextColumn::make('fecha_de_ingreso')
                    ->date()
                    ->sortable(),
                IconColumn::make('estado')
                    ->boolean(),
                TextColumn::make('cargo.nombre')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('categoria')
                    ->searchable(),
                TextColumn::make('persona.nombre')
                    ->label("Nombre")
                    ->numeric()
                    ->sortable(),
                TextColumn::make('persona.apellido')
                    ->label("Apellido")
                    ->numeric()
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
