<?php

namespace App\Filament\Resources\Personas\Pages;

use App\Filament\Resources\Personas\PersonaResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPersona extends EditRecord
{
    protected static string $resource = PersonaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ViewAction::make(),
        ];
    }
    public function getTitle(): string
    {
        $persona = $this->record;

        return "Editar el registro de {$persona->nombre} {$persona->apellido} (DNI: {$persona->dni})";
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
