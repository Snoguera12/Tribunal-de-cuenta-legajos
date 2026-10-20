<?php

namespace App\Filament\Resources\Licencias\Tables;

use App\Models\Licencia;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LicenciasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(Licencia::getFormSchema())
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('tipo_licencia')
                ->label('Tipo de licencia')
                ->options(fn () => Licencia::query()
                ->distinct()
                ->orderBy('tipo_licencia')
                ->pluck('tipo_licencia', 'tipo_licencia')
                ->all()),

            ])
            ->emptyStateHeading('Todavía no hay licencias registradas');
    }
}
