<?php

namespace App\Filament\Resources\Documentos\Pages;

use App\Filament\Resources\Documentos\DocumentoResource;
use App\Models\Legajo;
use Filament\Resources\Pages\CreateRecord;

class CreateDocumento extends CreateRecord
{
    protected static string $resource = DocumentoResource::class;

    // por si tocan el form, forzamos el legajo del empleado igual
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        if ($user->isEmpleado()) {
            $data['legajo_id'] = Legajo::where('persona_id', $user->persona_id)->value('id');
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
