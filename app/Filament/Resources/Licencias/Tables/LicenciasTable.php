<?php

namespace App\Filament\Resources\Licencias\Tables;

use App\Models\Licencia;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LicenciasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('legajo.num_legajo')
                    ->label('Legajo')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('legajo.persona.apellido')
                    ->label('Apellido')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('legajo.persona.nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('legajo.persona.dni')
                    ->label('DNI')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),

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
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('fecha_aprobacion')
                    ->label('Aprobada el')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('observaciones')
                    ->label('Observaciones')
                    ->state(fn (Licencia $record) => implode(' · ', $record->observaciones()) ?: null)
                    ->placeholder('-')
                    ->color('warning')
                    ->wrap(),

                TextColumn::make('created_at')
                    ->label('Recibida')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
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
