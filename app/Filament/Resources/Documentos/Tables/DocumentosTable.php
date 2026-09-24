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
            ->modifyQueryUsing(function (Builder $query) {
                // Filtrado personalizado directo a la consulta de Eloquent
                return $query->whereNull('descripcion')
                    ->orWhereNull('tipodoc')
                    ->orWhereNull('legajo_id');
            })
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
