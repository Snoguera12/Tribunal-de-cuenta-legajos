<?php

namespace App\Filament\Resources\Personas\Widgets;

use App\Models\Persona;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PersonaTotalWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Personas Registradas', Persona::count())
            //->description("Personas Registradas.")
            ->chart(
                Persona::selectRaw("strftime('%m', created_at) as month, COUNT(*) as count")
                    ->whereYear("created_at", now()->year)
                    ->groupBy("month")
                    ->orderBy("month")
                    ->pluck("count")
                    ->toArray()
            )
            ->descriptionColor("success")
            ->color("primary")
        ];
    }
}
