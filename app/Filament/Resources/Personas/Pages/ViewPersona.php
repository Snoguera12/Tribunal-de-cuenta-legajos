<?php

namespace App\Filament\Resources\Personas\Pages;

use App\Filament\Resources\Personas\PersonaResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPersona extends ViewRecord
{
    protected static string $resource = PersonaResource::class;
    public function getTitle(): string
    {
        return 'Perfil';
    }
    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
        ];
    }
}
