<?php

namespace App\Filament\Resources\Legajos\RelationManagers;

use App\Enums\TipodocEnum;
use App\Filament\Resources\Legajos\Pages\ViewLegajo;
use App\Models\Documento;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Documentos del legajo en forma de tabla, con buscador (solo lectura).
 */
class DocumentosRelationManager extends RelationManager
{
    protected static string $relationship = 'documentos';

    protected static ?string $title = 'Documentos';

    public static function canViewForRecord(Model $ownerRecord, string $pageName): bool
    {
        return $pageName === ViewLegajo::class
            && parent::canViewForRecord($ownerRecord, $pageName);
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(null)
            ->recordTitleAttribute('nombre_original')
            ->columns([
                ...Documento::getOutSchema('table'),
                TextColumn::make('tipodoc')->label('Tipo')->badge(),
                TextColumn::make('descripcion')->label('Descripción')->limit(60),
            ])
            ->filters([
                SelectFilter::make('tipodoc')->label('Tipo')->options(TipodocEnum::class),
            ])
            ->paginated([10, 25, 50]);
    }
}