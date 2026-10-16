<?php

namespace App\Filament\Resources\Incidentes\Pages;

use App\Filament\Resources\Incidentes\IncidenteResource;
use App\Models\Auditoria;
use Filament\Resources\Pages\EditRecord;


class EditIncidente extends EditRecord
{
    protected static string $resource = IncidenteResource::class;


    public function getTitle(): string
    {
        return 'Seguimiento del incidente #' . $this->record->id;
    }


    // si lo pasa a resuelto se anota quien y cuando
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (in_array($data['estado'], ['resuelto', 'descartado']) && ! $this->record->resuelto_en) {
            $data['resuelto_por'] = auth()->id();
            $data['resuelto_en'] = now();
        }

        return $data;
    }


    protected function afterSave(): void
    {
        Auditoria::registrar(
            evento: 'incidente_cerrado',
            descripcion: 'Incidente #' . $this->record->id . ' pasó a "' . $this->record->estado . '"',
            extra: ['incidente_id' => $this->record->id, 'notas' => $this->record->notas],
            severidad: 'info',
        );
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
