<?php

namespace App\Filament\Resources\Documentos\Tables;

use App\Models\Documento;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class DocumentosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(Documento::getOutSchema('table'))
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                //
            ]);
    }
}
