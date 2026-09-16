<?php

namespace App\Filament\Imports;

use App\Enums\EstadoCivilEnum;
use App\Enums\GeneroEnum;
use App\Models\Persona;
use Carbon\Carbon;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class PersonaImporter extends Importer
{
    protected static ?string $model = Persona::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nombre')->requiredMapping(),
            ImportColumn::make('apellido')->requiredMapping(),
            ImportColumn::make('dni')->requiredMapping(),
            ImportColumn::make('cuil')->requiredMapping(),
            ImportColumn::make('email'),
            ImportColumn::make('genero')->requiredMapping()
            ->castStateUsing(function (string $state): ?GeneroEnum {
                // 1. Limpiar el texto del Excel (quitar espacios y pasar a minúsculas)
                $cleanState = trim(mb_strtolower($state));

                // 2. Mapear textos comunes a tu Enum
                return match ($cleanState) {
                    'f', 'F', 'femenino', 'Femenino'   => GeneroEnum::Femenino,
                    'm', 'M', 'masculino', 'Masculino' => GeneroEnum::Masculino,
                    'x', 'X', 'otro', 'Otro', 'sin_determinar' => GeneroEnum::Otro,
                    default => null,
                };
            }),
            ImportColumn::make('estado_civil')->requiredMapping()
            ->castStateUsing(function (string $state): ?EstadoCivilEnum {
                // 1. Limpiar el texto del Excel (quitar espacios y pasar a minúsculas)
                $cleanState = trim(mb_strtolower($state));

                // 2. Mapear textos comunes a tu Enum
                return match ($cleanState) {
                    'soltero', 'soltera' => EstadoCivilEnum::Soltero,
                    'casado', 'casada'   => EstadoCivilEnum::Casado,
                    'divorciado', 'divorciada' => EstadoCivilEnum::Divorciado,
                    'viudo', 'viuda'     => EstadoCivilEnum::Viudo,
                    default => null,
                };
            }),
            ImportColumn::make('fecha_de_nacimiento')->requiredMapping()
            ->castStateUsing(function (string $state): ?string {
                if (blank($state)) return null;
                
                // Convierte dd/mm/aaaa a aaaa-mm-dd
                return Carbon::createFromFormat('d/m/Y', trim($state))->format('Y-m-d');
            }),
            ImportColumn::make('domicilio'),
            ImportColumn::make('telefono'),
            ImportColumn::make('teléfono_emergencia'),
        ];
    }

    public function resolveRecord(): Persona
    {
        return Persona::firstOrNew([
            'DNI' => $this->data['DNI'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your persona import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
