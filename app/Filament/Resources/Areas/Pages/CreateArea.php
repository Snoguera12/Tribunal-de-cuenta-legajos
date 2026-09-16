<?php

namespace App\Filament\Resources\Areas\Pages;

use App\Filament\Resources\Areas\AreaResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateArea extends CreateRecord
{
    protected static string $resource = AreaResource::class;
    public function getTitle(): string
    {
        return 'Añadir Área';
    }

    public function getBreadcrumb(): string
    {
        return 'Añadir';
    }

    // Cambia el texto del botón de enviar del formulario
    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Añadir Área');
    }
    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()
            ->label('Añadir y añadir otro');
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
