<?php

namespace App\Filament\Resources\Institucions\Tables;

use App\Models\Institucion;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class InstitucionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(Institucion::getOutSchema('table'))
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
