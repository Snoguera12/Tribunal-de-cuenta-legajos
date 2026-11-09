<?php

namespace App\Filament\Resources\Categorias\Tables;

use App\Filament\Tables\ConteoLegajos;
use App\Models\Categoria;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class CategoriasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(Categoria::getOutSchema())
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