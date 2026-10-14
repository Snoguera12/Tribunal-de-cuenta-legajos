<?php

namespace App\Filament\Resources\Legajos\RelationManagers;

use App\Filament\Resources\Legajos\Pages\ViewLegajo;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Historial de licencias dentro de cada legajo (solo lectura).
 * Se muestra únicamente en la página de vista, no en la de edición.
 * Las licencias las registra el Sistema de Licencias por la API.
 */
class LicenciasRelationManager extends RelationManager
{
    protected static string $relationship = 'licencias';

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
            ->recordTitleAttribute('id_solicitud')
            ->columns([
                TextColumn::make('tipo_licencia')
                    ->label('Tipo')
                    ->searchable(),

                TextColumn::make('fecha_inicio')
                    ->label('Desde')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('fecha_fin')
                    ->label('Hasta')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('dias_solicitados')
                    ->label('Días')
                    ->numeric(),

                TextColumn::make('nro_resolucion')
                    ->label('N° resolución')
                    ->placeholder('-'),

                TextColumn::make('fecha_aprobacion')
                    ->label('Aprobada el')
                    ->date('d/m/Y'),

                TextColumn::make('motivo')
                    ->label('Motivo')
                    ->placeholder('-')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('fecha_inicio', 'desc')
            ->emptyStateHeading('Este legajo no tiene licencias registradas');
    }
}
