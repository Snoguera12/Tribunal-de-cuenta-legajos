<?php

namespace App\Filament\Services\Exports;

use App\Models\Legajo;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

/**
 * Informe de legajos (lista) en Excel o CSV.
 * Se lanza desde el botón "Exportar" de la lista de legajos.
 */
class LegajoExporter extends Exporter
{
    protected static ?string $model = Legajo::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('num_legajo')->label('Nº de legajo'),
            ExportColumn::make('persona.apellido')->label('Apellido'),
            ExportColumn::make('persona.nombre')->label('Nombre'),
            ExportColumn::make('persona.dni')->label('DNI'),
            ExportColumn::make('persona.cuil')->label('CUIL'),
            ExportColumn::make('cargo.nombre')->label('Cargo'),
            ExportColumn::make('area.nombre')->label('Área'),
            ExportColumn::make('categoria.nombre')->label('Categoría'),
            ExportColumn::make('tipo_contrato')
                ->label('Tipo de contrato')
                ->formatStateUsing(fn ($state) => $state?->getLabel()),
            ExportColumn::make('fecha_de_ingreso')->label('Fecha de ingreso'),
            ExportColumn::make('estado')
                ->label('Estado')
                ->formatStateUsing(fn ($state) => $state?->getLabel()),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $cuerpo = 'El informe de legajos terminó: ' . Number::format($export->successful_rows) . ' legajos exportados.';

        if ($fallidas = $export->getFailedRowsCount()) {
            $cuerpo .= ' ' . Number::format($fallidas) . ' no se pudieron exportar.';
        }

        return $cuerpo;
    }
}
