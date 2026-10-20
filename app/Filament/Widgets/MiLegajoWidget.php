<?php

namespace App\Filament\Widgets;

use App\Models\Legajo;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Escritorio del Empleado: resumen de su propio legajo (número, cargo, antigüedad y contratación).
 * Solo lee los datos del usuario autenticado (por su persona_id).
 */
class MiLegajoWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -2;

    public static function canView(): bool
    {
        return auth()->user()?->isEmpleado() ?? false;
    }

    protected function getStats(): array
    {
        $personaId = auth()->user()->persona_id;

        // Legajos de alta de la persona vinculada; si tuviera más de uno se muestra el más antiguo.
        $legajos = $personaId
            ? Legajo::query()
                ->where('persona_id', $personaId)
                ->where('estado', true)
                ->with(['cargo', 'area', 'categoria'])
                ->orderBy('fecha_de_ingreso')
                ->get()
            : collect();

        $legajo = $legajos->first();

        if (! $legajo) {
            return [
                Stat::make('Mi legajo', 'Sin legajo asignado')
                    ->description('RRHH todavía no registró un legajo de alta para tu usuario.')
                    ->descriptionIcon('heroicon-m-information-circle')
                    ->color('gray'),
            ];
        }

        $otros = $legajos->count() - 1;
        $ingreso = Carbon::parse($legajo->fecha_de_ingreso);

        return [
            Stat::make('Legajo N°', $legajo->num_legajo ?? 'Sin número')
                ->description('De alta' . ($otros > 0 ? " · y {$otros} más" : ''))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Cargo', $legajo->cargo?->nombre ?? '—')
                ->description('Área: ' . ($legajo->area?->nombre ?? '—'))
                ->descriptionIcon('heroicon-m-building-office')
                ->color('primary'),

            Stat::make('Antigüedad', $this->antiguedad($ingreso))
                ->description('Ingreso: ' . $ingreso->format('d/m/Y'))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),

            Stat::make('Contratación', $legajo->tipo_contrato?->getLabel() ?? '—')
                ->description('Categoría: ' . ($legajo->categoria?->nombre ?? '—'))
                ->descriptionIcon('heroicon-m-identification')
                ->color('primary'),
        ];
    }

    private function antiguedad(Carbon $ingreso): string
    {
        if ($ingreso->isFuture()) {
            return 'Aún no ingresó';
        }

        $diff = $ingreso->diff(now());
        $partes = [];

        if ($diff->y > 0) {
            $partes[] = $diff->y . ($diff->y === 1 ? ' año' : ' años');
        }

        if ($diff->m > 0) {
            $partes[] = $diff->m . ($diff->m === 1 ? ' mes' : ' meses');
        }

        return $partes ? implode(' y ', $partes) : 'Menos de un mes';
    }
}
