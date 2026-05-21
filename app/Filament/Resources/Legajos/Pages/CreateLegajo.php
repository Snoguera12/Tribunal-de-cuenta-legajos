<?php

namespace App\Filament\Resources\Legajos\Pages;

use App\Filament\Resources\Legajos\LegajoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLegajo extends CreateRecord
{
    protected static string $resource = LegajoResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['fecha_de_ingreso'])) {
            $data['fecha_de_ingreso'] = now();
        }

        return $data;
    }
}
