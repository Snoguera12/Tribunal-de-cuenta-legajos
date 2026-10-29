<?php

namespace App\Filament\Resources\Legajos\Pages;

use App\Filament\Actions\MotivoBajaAction;
use App\Filament\Resources\Legajos\LegajoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLegajo extends ViewRecord
{
    protected static string $resource = LegajoResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        if ($this->record->persona_id) {
            \App\Models\ActividadReciente::registrar('agente', $this->record->persona_id, 'visto');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            MotivoBajaAction::make(),
        ];
    }
}
