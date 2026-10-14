<?php

namespace App\Filament\Resources\Legajos\Pages;

use App\Filament\Actions\MotivoBajaAction;
use App\Filament\Resources\Legajos\LegajoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLegajo extends ViewRecord
{
    protected static string $resource = LegajoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            MotivoBajaAction::make(),
        ];
    }
}
