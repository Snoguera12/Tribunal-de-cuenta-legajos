<?php

namespace App\Filament\Resources\Personas\Pages;

use App\Filament\Resources\Personas\PersonaResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreatePersona extends CreateRecord
{
    protected static string $resource = PersonaResource::class;
    public function getTitle(): string
    {
        return 'Registrar Persona';
    }

    public function getBreadcrumb(): string
    {
        return 'Registrar';
    }

    // Cambia el texto del botón de enviar del formulario
    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Registrar Persona');
    }
    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()
            ->label('Registrar y crear otro');
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
