<?php

namespace App\Filament\Widgets;

use App\Enums\TipodocEnum;
use App\Filament\Resources\Personas\PersonaResource;
use App\Models\Documento;
use App\Models\Legajo;
use App\Models\Persona;
use Filament\Widgets\Widget;

/**
 * Escritorio del Empleado: qué tan completa está su ficha y qué falta cargar.
 *
 * El Empleado solo tiene permiso de lectura (PersonaPolicy::update es de Admin/RRHH), por eso los
 * enlaces abren la VISTA de su ficha en la pestaña correspondiente, no el formulario de edición.
 */
class CompletitudFichaWidget extends Widget
{
    protected static ?int $sort = -1;

    protected string $view = 'filament.widgets.completitud-ficha';

    protected int | string | array $columnSpan = 'full';

    /** Campos de la persona que cuentan como "datos personales completos". */
    private const CAMPOS_PERSONALES = [
        'nombre' => 'nombre',
        'apellido' => 'apellido',
        'dni' => 'DNI',
        'cuil' => 'CUIL',
        'email' => 'email',
        'genero' => 'género',
        'estado_civil' => 'estado civil',
        'fecha_de_nacimiento' => 'fecha de nacimiento',
        'domicilio' => 'domicilio',
        'telefono' => 'teléfono',
    ];

    public static function canView(): bool
    {
        return auth()->user()?->isEmpleado() ?? false;
    }

    protected function getViewData(): array
    {
        $personaId = auth()->user()->persona_id;
        $persona = $personaId ? Persona::query()->find($personaId) : null;

        if (! $persona) {
            return ['persona' => null, 'items' => [], 'porcentaje' => 0, 'completos' => 0, 'total' => 0];
        }

        $items = $this->items($persona);
        $completos = collect($items)->where('ok', true)->count();
        $total = count($items);

        return [
            'persona' => $persona,
            'items' => $items,
            'completos' => $completos,
            'total' => $total,
            'porcentaje' => $total > 0 ? (int) round($completos / $total * 100) : 0,
        ];
    }

    /** Lista de requisitos: para sumar o quitar uno, se edita solo este método. */
    private function items(Persona $persona): array
    {
        $faltantes = collect(self::CAMPOS_PERSONALES)
            ->filter(fn (string $etiqueta, string $campo) => blank($persona->{$campo}))
            ->values();

        $legajoIds = Legajo::query()->where('persona_id', $persona->id)->pluck('id');
        $tieneDni = $legajoIds->isNotEmpty() && Documento::query()
            ->whereIn('legajo_id', $legajoIds)
            ->where('tipodoc', TipodocEnum::DNI->value)
            ->exists();

        return [
            [
                'titulo' => 'Datos personales',
                'ok' => $faltantes->isEmpty(),
                'detalle' => $faltantes->isEmpty() ? 'Completos' : 'Faltan: ' . $faltantes->implode(', '),
                'url' => $this->url($persona, 'persona'),
            ],
            $this->cantidad($persona, 'Familiares', $persona->familiares()->count(), 'familiar'),
            $this->cantidad($persona, 'Idiomas', $persona->idiomas()->count(), 'idioma'),
            $this->cantidad($persona, 'Estudios y títulos', $persona->estudios()->count(), 'estudio'),
            $this->cantidad($persona, 'Cursos', $persona->cursos()->count(), 'curso'),
            $this->cantidad($persona, 'Antecedentes laborales', $persona->antecedentesLaborales()->count(), 'antecedenteslaborales'),
            [
                'titulo' => 'Documento de identidad (DNI)',
                'ok' => $tieneDni,
                'detalle' => $tieneDni ? 'Cargado' : 'Sin cargar',
                'url' => $this->url($persona, 'legajo'),
            ],
        ];
    }

    private function cantidad(Persona $persona, string $titulo, int $cantidad, string $pestana): array
    {
        return [
            'titulo' => $titulo,
            'ok' => $cantidad > 0,
            'detalle' => $cantidad > 0 ? "{$cantidad} registrado" . ($cantidad > 1 ? 's' : '') : 'Sin registros',
            'url' => $this->url($persona, $pestana),
        ];
    }

    private function url(Persona $persona, string $pestana): string
    {
        return PersonaResource::getUrl('view', ['record' => $persona, 'tab' => $pestana]);
    }
}
