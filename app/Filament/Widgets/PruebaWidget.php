<?php

namespace App\Filament\Widgets;

use App\Models\Persona;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PruebaWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Personas Registradas', Persona::count())
            ->description("Número total de las Personas de este año.")
            ->chart(
                Persona::selectRaw("strftime('%m', created_at) as month, COUNT(*) as count")
                    ->whereYear("created_at", now()->year)
                    ->groupBy("month")
                    ->orderBy("month")
                    ->pluck("count")
                    ->toArray()
            )
            ->descriptionColor("success")
            ->color("success")
        ];
    }
}
