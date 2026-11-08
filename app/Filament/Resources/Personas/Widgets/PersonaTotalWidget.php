<?php

namespace App\Filament\Resources\Personas\Widgets;

use App\Models\Persona;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;


class PersonaTotalWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 1;
    protected int | array | null $columns = 1;
    public static function canView(): bool
    {
        return auth()->user()->isStaffRoles();
    }
    protected function getStats(): array
    {
        return [
            Stat::make('Personas registradas', Persona::count())
            ->description('Total en el sistema.')
            ->descriptionColor("success")
            ->color("primary")
        ];
    }
}
