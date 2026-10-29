<?php

namespace App\Filament\Resources\Personas\Pages;

use App\Filament\Resources\Personas\PersonaResource;
use App\Models\Auditoria;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Js;

class ViewPersona extends ViewRecord
{
    protected static string $resource = PersonaResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        Auditoria::registrar(
            evento: 'ver_persona',
            descripcion: 'Abrió el perfil de ' . $this->record->nombre . ' ' . $this->record->apellido,
            modelo: $this->record,
        );

        \App\Models\ActividadReciente::registrar('agente', $this->record->id, 'visto');
    }

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
