<?php

namespace App\Filament\Resources\Documentos\Tables;

use App\Models\Documento;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\Filter;

class DocumentosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(Documento::getOutSchema('table'))
            ->filters([
                Filter::make('ocultar_adjuntados')
                ->label('Ocultar los ya adjuntados')
                ->toggle()
                ->default()
                ->query(fn (Builder $query) => $query->doesntHave('legajos')),
            ])
            ->recordActions([
                EditAction::make()->label('Adjuntar'),
            ])
            ->toolbarActions([
                //
            ]);
    }
}
