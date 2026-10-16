<?php

namespace App\Filament\Resources\Documentos\Tables;

use App\Models\Documento;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DocumentosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(Documento::getOutSchema('table'))
            // esta lista muestra solo los pendientes de completar
            ->modifyQueryUsing(fn (Builder $query) => $query->pendientes())
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                ->label('Adjuntar'),
            ])
            ->toolbarActions([
                //
            ]);
    }
}
