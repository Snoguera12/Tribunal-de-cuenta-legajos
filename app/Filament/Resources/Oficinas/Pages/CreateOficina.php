<?php

namespace App\Filament\Resources\Oficinas\Pages;

use App\Filament\Resources\Oficinas\OficinaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOficina extends CreateRecord
{
    protected static string $resource = OficinaResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['fecha_creacion'])) {
            $data['fecha_creacion'] = now();
        }

        return $data;
    }
}
