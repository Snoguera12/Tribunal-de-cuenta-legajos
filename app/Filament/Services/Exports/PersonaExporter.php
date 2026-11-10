<?php

namespace App\Filament\Services\Exports;

use App\Enums\EstadoCivilEnum;
use App\Enums\GeneroEnum;
use App\Models\Persona;
use App\Services\ProtegeFormulas;
use Carbon\Carbon;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class PersonaExporter extends Exporter
{
    protected static ?string $model = Persona::class;

    public static function getColumns(): array
    {
        $texto = fn ($state) => ProtegeFormulas::texto($state);

        return [
            ExportColumn::make('nombre')->formatStateUsing($texto),
            ExportColumn::make('apellido')->formatStateUsing($texto),
            ExportColumn::make('dni'),
            ExportColumn::make('cuil'),
            ExportColumn::make('email')->formatStateUsing($texto),

            // con el texto que se lee en pantalla (el mismo que acepta el importador)
            ExportColumn::make('genero')
                ->formatStateUsing(fn ($state) => ($state instanceof GeneroEnum ? $state : GeneroEnum::tryFrom((int) $state))?->getLabel()),
            ExportColumn::make('estado_civil')
                ->formatStateUsing(fn ($state) => ($state instanceof EstadoCivilEnum ? $state : EstadoCivilEnum::tryFrom((int) $state))?->getLabel()),

            // dd/mm/aaaa: el mismo formato que pide el importador, para poder exportar y volver a importar
            ExportColumn::make('fecha_de_nacimiento')
                ->formatStateUsing(fn ($state) => filled($state) ? Carbon::parse($state)->format('d/m/Y') : null),

            ExportColumn::make('domicilio')->formatStateUsing($texto),
            ExportColumn::make('telefono')->formatStateUsing($texto),
            ExportColumn::make('telefono_emergencia')->formatStateUsing($texto),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $cuerpo = 'La exportación de personas terminó: se exportaron ' . Number::format($export->successful_rows) . ' fila(s).';

        if ($fallidas = $export->getFailedRowsCount()) {
            $cuerpo .= ' ' . Number::format($fallidas) . ' fila(s) fallaron.';
        }

        return $cuerpo;
    }
}
