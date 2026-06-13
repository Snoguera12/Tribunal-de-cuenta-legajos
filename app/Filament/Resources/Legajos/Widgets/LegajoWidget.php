<?php

namespace App\Filament\Resources\Legajos\Widgets;

use App\Models\Legajo;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LegajoWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {

        $total = Legajo::query()->count();
        $alta = Legajo::query()->where('estado', true)->count();
        $baja = Legajo::query()->where('estado', false)->count();

        $porcentajeAltas = $total > 0 ? round(($alta / $total) * 100) : 0;
        $porcentajeBajas = $total > 0 ? round(($baja / $total) * 100) : 0;

        return [
            Stat::make('Total Legajos', $total)
            ->description('Todos los legajos.')
            ->color('primary'),

            Stat::make('De Alta', $alta)
            ->description("Legajos de alta. {$porcentajeAltas}% del total.")
            ->descriptionIcon('heroicon-m-check-circle')
            ->color('success'),

            Stat::make('De Baja', $baja)
            ->description("Legajos de baja. {$porcentajeBajas}% del total.")
            ->descriptionIcon('heroicon-m-x-circle')
            ->color('danger')
        ];
    }
}
