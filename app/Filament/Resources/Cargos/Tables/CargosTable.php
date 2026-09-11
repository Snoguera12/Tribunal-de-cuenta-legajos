<?php

namespace App\Filament\Resources\Cargos\Tables;

use App\Models\Cargo;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CargosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(Cargo::getOutSchema())
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                ->label('Editar')
                ->iconButton()
                ->color('primary')
                ->icon('heroicon-m-pencil-square'),
            ])
            ->toolbarActions([
                //
            ]);
    }
}
