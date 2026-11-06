<?php

namespace App\Filament\Resources\Historialbajas\Tables;

use App\Filament\Resources\Legajos\LegajoResource;
use App\Models\Historialbaja;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class HistorialbajasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(Historialbaja::getFormSchema())
            ->recordUrl(fn (Historialbaja $record): ?string => $record->legajo_id
            ? LegajoResource::getUrl('view', ['record' => $record->legajo_id])
            : null)
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
