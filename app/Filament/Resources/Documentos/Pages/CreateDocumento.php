<?php

namespace App\Filament\Resources\Documentos\Pages;

use App\Filament\Resources\Documentos\DocumentoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDocumento extends CreateRecord
{
    protected static string $resource = DocumentoResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['fecha_de_subida'])) {
            $data['fecha_de_subida'] = now();
        }
        if (empty($data['fecha_de_creacion'])) {
            $data['fecha_de_creacion'] = now();
        }

        return $data;
    }
}
