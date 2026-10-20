<?php

namespace App\Filament\Resources\Personas\Pages;

use App\Filament\Resources\Personas\PersonaResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Js;

class ViewPersona extends ViewRecord
{
    protected static string $resource = PersonaResource::class;
    public function getTitle(): string
    {
        $persona = $this->record;

        return "Perfil de {$persona->nombre} {$persona->apellido} (DNI: {$persona->dni})";
    }
    protected function getHeaderActions(): array
    {
        $edicion = Js::from(PersonaResource::getUrl('edit', ['record' => $this->record]));

        return [
            EditAction::make()
            ->extraAttributes([
                'onclick' => "event.preventDefault();"
                    . "const tab = new URLSearchParams(window.location.search).get('tab');"
                    . "window.location.href = {$edicion} + (tab ? '?tab=' + encodeURIComponent(tab) : '');",
            ]),
        ];
    }
}
