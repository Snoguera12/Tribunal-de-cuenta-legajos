<?php

namespace App\Filament\Resources\Personas\Pages;

use App\Filament\Resources\Personas\PersonaResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Livewire\Attributes\Url;

class CreatePersona extends CreateRecord
{
    protected static string $resource = PersonaResource::class;

    // viene en 1 solo cuando el usuario entra desde "Mis datos personales"
    #[Url]
    public bool $propia = false;

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
    protected function afterCreate(): void
    {
        $user = auth()->user();

        // solo se vincula si es SU persona (entro desde "Mis datos personales") y no tiene otra
        // si no, el admin/RRHH quedaria dueño de la primera persona que cargue
        if ($this->propia && ! $user->persona_id) {
            $user->update([
                'persona_id' => $this->record->id,
            ]);
        }
    }
}
