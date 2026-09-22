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
        return [
            Stat::make('Personas Registradas', Persona::count())
            ->descriptionColor("success")
            ->color("primary")
        ];
    }
}
