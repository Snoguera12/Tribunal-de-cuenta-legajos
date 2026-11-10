<?php

namespace App\Filament\Services\Imports;

use App\Enums\EstadoCivilEnum;
use App\Enums\GeneroEnum;
use App\Models\Persona;
use Carbon\Carbon;
use Closure;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class PersonaImporter extends Importer
{
    protected static ?string $model = Persona::class;

    // textos que acepta el importador (también los que genera el exportador)
    public static function generoDesde(?string $texto): ?GeneroEnum
    {
        return match (trim(mb_strtolower((string) $texto))) {
            'f', 'femenino' => GeneroEnum::Femenino,
            'm', 'masculino' => GeneroEnum::Masculino,
            'x', 'otro', 'sin_determinar' => GeneroEnum::Otro,
            default => null,
        };
    }

    public static function estadoCivilDesde(?string $texto): ?EstadoCivilEnum
    {
        return match (trim(mb_strtolower((string) $texto))) {
            'soltero', 'soltera', 'soltero/a' => EstadoCivilEnum::Soltero,
            'casado', 'casada', 'casado/a' => EstadoCivilEnum::Casado,
            'divorciado', 'divorciada', 'divorciado/a' => EstadoCivilEnum::Divorciado,
            'viudo', 'viuda', 'viudo/a' => EstadoCivilEnum::Viudo,
            default => null,
        };
    }

    // Filament convierte los valores (castStateUsing) ANTES de validar. Si el texto se reconoció llega
    // el enum; si no, se deja el texto original para que esta regla muestre un mensaje claro.
    private static function reconocido(string $mensaje): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($mensaje): void {
            if (! $value instanceof \BackedEnum) {
                $fail($mensaje);
            }
        };
    }

    // dd/mm/aaaa => aaaa-mm-dd; si no es una fecha real devuelve el texto original (lo rechaza la regla)
    public static function fechaDesde(?string $texto): ?string
    {
        $texto = trim((string) $texto);

        if ($texto === '') {
            return null;
        }

        try {
            $fecha = Carbon::createFromFormat('!d/m/Y', $texto);
        } catch (\Throwable) {
            return $texto;
        }

        if ($fecha === false || $fecha->format('d/m/Y') !== $texto) {
            return $texto;
        }

        return $fecha->format('Y-m-d');
    }

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nombre')->requiredMapping()->rules(['required', 'max:100']),
            ImportColumn::make('apellido')->requiredMapping()->rules(['required', 'max:100']),
            ImportColumn::make('dni')->requiredMapping()->rules(['required', 'integer', 'min:1', 'max:2147483647']),
            ImportColumn::make('cuil')->requiredMapping()->rules(['required', 'integer', 'min:1']),
            ImportColumn::make('email')->requiredMapping()->rules(['required', 'email', 'max:50']),

            ImportColumn::make('genero')->requiredMapping()
                ->rules(['required', self::reconocido('El género tiene que ser Femenino, Masculino u Otro.')])
                ->castStateUsing(fn (string $state): GeneroEnum|string => self::generoDesde($state) ?? $state),

            ImportColumn::make('estado_civil')->requiredMapping()
                ->rules(['required', self::reconocido('El estado civil tiene que ser Soltero/a, Casado/a, Divorciado/a o Viudo/a.')])
                ->castStateUsing(fn (string $state): EstadoCivilEnum|string => self::estadoCivilDesde($state) ?? $state),

            ImportColumn::make('fecha_de_nacimiento')->requiredMapping()
                ->rules([
                    'required',
                    // llega ya convertida a aaaa-mm-dd; si no era una fecha real queda el texto original y falla acá
                    function (string $attribute, mixed $value, Closure $fail): void {
                        $partes = explode('-', (string) $value);

                        if (count($partes) !== 3 || ! ctype_digit(implode('', $partes)) || ! checkdate((int) $partes[1], (int) $partes[2], (int) $partes[0])) {
                            $fail('La fecha de nacimiento tiene que ser una fecha real con formato dd/mm/aaaa.');
                        }
                    },
                ])
                ->castStateUsing(fn (string $state): ?string => self::fechaDesde($state)),

            ImportColumn::make('domicilio')->rules(['nullable', 'max:250']),
            ImportColumn::make('telefono')->rules(['nullable', 'max:20']),
            // antes se llamaba "teléfono_emergencia" (con tilde) y no coincidía con la columna de la base
            ImportColumn::make('telefono_emergencia')
                ->guess(['teléfono_emergencia', 'teléfono emergencia', 'telefono emergencia'])
                ->rules(['nullable', 'max:20']),
        ];
    }

    // si ya hay una persona con ese DNI se actualiza (queda en la auditoría y en el historial); si no, se crea
    public function resolveRecord(): Persona
    {
        return Persona::firstOrNew([
            'dni' => $this->data['dni'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $cuerpo = $import->successful_rows > 0
            ? 'La importación de personas terminó: se importaron ' . Number::format($import->successful_rows) . ' fila(s).'
            : 'La importación de personas terminó: NO se guardó ninguna fila.';

        if ($fallidas = $import->getFailedRowsCount()) {
            $cuerpo .= ' ' . Number::format($fallidas) . ' fila(s) fallaron: descargá el archivo de filas con error para ver el motivo de cada una.';
        }

        return $cuerpo;
    }
}
