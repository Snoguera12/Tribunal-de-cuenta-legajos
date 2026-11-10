<?php

namespace App\Filament\Resources\Cargos\Tables;

use App\Filament\Tables\ConteoLegajos;
use App\Models\Cargo;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class CargosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(Cargo::getOutSchema())
            ->filters([ ConteoLegajos::filtro(), ])
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
