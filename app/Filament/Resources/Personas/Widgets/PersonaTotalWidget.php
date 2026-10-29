<?php

namespace App\Filament\Resources\Personas\Widgets;

use App\Models\Persona;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PersonaTotalWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return auth()->user()->isStaffRoles();
    }
    protected function getStats(): array
    {
        // agrupa por mes en php, asi funciona en sqlite y mysql
        $porMes = Persona::query()
            ->whereYear('created_at', now()->year)
            ->pluck('created_at')
            ->groupBy(fn ($fecha) => \Illuminate\Support\Carbon::parse($fecha)->format('m'))
            ->map->count();

        $chart = collect(range(1, 12))
            ->map(fn ($mes) => $porMes->get(str_pad((string) $mes, 2, '0', STR_PAD_LEFT), 0))
            ->toArray();

        return [
            Stat::make('Personas Registradas', Persona::count())
            //->description("Personas Registradas.")
            ->chart($chart)
            ->descriptionColor("success")
            ->color("primary")
        ];
    }
}
