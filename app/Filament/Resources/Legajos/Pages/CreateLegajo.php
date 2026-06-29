<?php

namespace App\Filament\Resources\Legajos\Pages;

use App\Filament\Resources\Legajos\LegajoResource;
use App\Filament\Resources\Personas\PersonaResource;
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

    // 1. Declaramos una propiedad pública para guardar el estado del origen
    public ?string $origenPersonaId = null;

    // 2. Capturamos el parámetro de la URL justo cuando se monta la página
    public function mount(): void
    {
        parent::mount();

        // Guardamos el ID en nuestra propiedad persistente de Livewire
        $this->origenPersonaId = request()->query('persona_id');
    }

    // 3. Evaluamos la propiedad persistente al redireccionar
    protected function getRedirectUrl(): string
    {
        // Si al iniciar la página venía de una persona, regresamos a ella
        if ($this->origenPersonaId && $this->record->persona_id) {
            return PersonaResource::getUrl('view', ['record' => $this->record->persona_id]);
        }

        // Si se creó de forma aislada, regresa al listado general de legajos
        return $this->getResource()::getUrl('index');
    }

}
