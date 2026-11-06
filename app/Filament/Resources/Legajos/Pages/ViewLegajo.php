<?php

namespace App\Filament\Resources\Legajos\Pages;

use App\Filament\Actions\MotivoBajaAction;
use App\Filament\Resources\Legajos\LegajoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

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

    public function getTitle(): string|Htmlable
    {
        $legajo = $this->getRecord()->loadMissing('persona');
        $persona = $legajo->persona;

        $nombre = trim(($persona?->nombre ?? '') . ' ' . ($persona?->apellido ?? ''));

        return "Legajo de {$nombre} (DNI: {$persona?->dni} - NºLegajo: {$legajo->num_legajo})";
    }
}
