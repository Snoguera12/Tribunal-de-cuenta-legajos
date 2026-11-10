<?php

namespace App\Filament\Resources\Legajos\Pages;

use App\Filament\Actions\MotivoBajaAction;
use App\Filament\Resources\Legajos\LegajoResource;
use App\Models\Auditoria;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewLegajo extends ViewRecord
{
    protected static string $resource = LegajoResource::class;

    // las lecturas también se auditan
    public function mount(int | string $record): void
    {
        parent::mount($record);

        Auditoria::registrar(
            evento: 'ver_legajo',
            descripcion: 'Abrió el legajo Nº ' . $this->record->num_legajo,
            modelo: $this->record,
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            MotivoBajaAction::make(),
        ];
    }

    public function getTitle(): string|Htmlable
    {
        $legajo = $this->getRecord()->loadMissing('persona');
        $persona = $legajo->persona;

        $nombre = trim(($persona?->nombre ?? '') . ' ' . ($persona?->apellido ?? ''));

        return "Legajo de {$nombre} (DNI: {$persona?->dni} - NºLegajo: {$legajo->num_legajo})";
    }
}
