<?php

namespace App\Filament\Exports;

use App\Models\Persona;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class PersonaExporter extends Exporter
{
    protected static ?string $model = Persona::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('nombre'),
            ExportColumn::make('apellido'),
            ExportColumn::make('dni'),
            ExportColumn::make('cuil'),
            ExportColumn::make('email'),
            ExportColumn::make('genero'),
            ExportColumn::make('estado_civil'),
            ExportColumn::make('fecha_de_nacimiento'),
            ExportColumn::make('domicilio'),
            ExportColumn::make('telefono'),
            ExportColumn::make('teléfono_emergencia'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your persona export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
