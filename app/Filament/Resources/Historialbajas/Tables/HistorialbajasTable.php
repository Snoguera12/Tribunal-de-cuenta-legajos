<?php

namespace App\Filament\Resources\Historialbajas\Tables;

use App\Models\Historialbaja;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class HistorialbajasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(Historialbaja::getFormSchema())
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
