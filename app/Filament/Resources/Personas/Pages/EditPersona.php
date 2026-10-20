<?php

namespace App\Filament\Resources\Personas\Pages;

use App\Filament\Resources\Personas\PersonaResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Js;

class EditPersona extends EditRecord
{
    protected static string $resource = PersonaResource::class;

    protected function getHeaderActions(): array
    {
        $vista = Js::from(PersonaResource::getUrl('view', ['record' => $this->record]));

        return [
            //DeleteAction::make(),
            ViewAction::make()
            ->extraAttributes([
                'onclick' => "event.preventDefault();"
                    . "const tab = new URLSearchParams(window.location.search).get('tab');"
                    . "window.location.href = {$vista} + (tab ? '?tab=' + encodeURIComponent(tab) : '');",
            ]),
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
