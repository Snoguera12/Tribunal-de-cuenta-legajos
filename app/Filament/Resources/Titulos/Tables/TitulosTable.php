<?php

namespace App\Filament\Resources\Titulos\Tables;

use App\Models\Titulo;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class TitulosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(Titulo::getOutSchema('table'))
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                /*BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),*/
            ]);
    }
}
