<?php

namespace App\Filament\Resources\PersonaFamiliars\Pages;

use App\Filament\Resources\PersonaFamiliars\PersonaFamiliarResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPersonaFamiliar extends EditRecord
{
    protected static string $resource = PersonaFamiliarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
