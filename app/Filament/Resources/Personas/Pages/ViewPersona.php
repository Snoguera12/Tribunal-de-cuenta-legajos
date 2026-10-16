<?php

namespace App\Filament\Resources\Personas\Pages;

use App\Filament\Resources\Personas\PersonaResource;
use App\Models\Auditoria;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

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
    }

    public function getTitle(): string
    {
        $persona = $this->record;

        return "Perfil de {$persona->nombre} {$persona->apellido} (DNI: {$persona->dni})";
    }
    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
        ];
    }
}
