<?php

namespace App\Filament\Resources\Areas\Tables;

use App\Models\Area;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class AreasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(Area::getOutSchema())
            ->filters([])
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