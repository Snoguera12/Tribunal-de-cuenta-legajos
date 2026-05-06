<?php

namespace App\Filament\Resources\PersonaFamiliars\Pages;

use App\Filament\Resources\PersonaFamiliars\PersonaFamiliarResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPersonaFamiliars extends ListRecords
{
    protected static string $resource = PersonaFamiliarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
