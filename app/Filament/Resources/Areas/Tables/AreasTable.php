<?php

namespace App\Filament\Resources\Areas\Tables;

use App\Filament\Tables\ConteoLegajos;
use App\Models\Area;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class AreasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(Area::getOutSchema())
            ->filters([ ConteoLegajos::filtro(), ])
            ->recordActions([
                EditAction::make()
                ->label('Editar')
                ->iconButton()
                ->color('primary')
                ->icon('heroicon-m-pencil-square'),
            ])
            ->toolbarActions([]);
    }
}