<?php

namespace App\Filament\Resources\Cargos\Tables;

use App\Filament\Tables\ConteoLegajos;
use App\Models\Cargo;
use Filament\Actions\DeleteAction;
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
                
                DeleteAction::make()
                ->label('Borrar')
                ->iconButton()
                ->color('danger')
                ->icon('heroicon-m-trash')
                ->modalHeading('¿Borrar este cargo?')
                ->modalDescription('Se elimina de la lista de cargos. Esta acción no se puede deshacer.'),
            ])
            ->toolbarActions([
                //
            ]);
    }
}
