<?php

namespace App\Filament\Resources\Legajos\RelationManagers;

use App\Filament\Resources\Legajos\Pages\ViewLegajo;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Historial de licencias dentro de cada legajo (solo lectura).
 * Se muestra únicamente en la página de vista, no en la de edición.
 * Las licencias las registra el Sistema de Licencias por la API.
 */
class LicenciasRelationManager extends RelationManager
{
    protected static string $relationship = 'documentos';

    protected static ?string $title = 'Licencias';

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
            ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('licencia'))
            ->columns([
                TextColumn::make('licencia.tipo_licencia')->label('Tipo')->searchable(),
                TextColumn::make('licencia.fecha_inicio')->label('Desde')->date('d/m/Y')->sortable(),
                TextColumn::make('licencia.fecha_fin')->label('Hasta')->date('d/m/Y')->sortable(),
                TextColumn::make('licencia.dias_solicitados')->label('Días')->numeric(),
                TextColumn::make('licencia.nro_resolucion')->label('N° resolución')->placeholder('-'),
                TextColumn::make('licencia.fecha_aprobacion')->label('Aprobada el')->date('d/m/Y')->sortable(),
            ]);
    }
}
