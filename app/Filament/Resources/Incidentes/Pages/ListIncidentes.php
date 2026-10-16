<?php

namespace App\Filament\Resources\Incidentes\Pages;

use App\Filament\Concerns\DescargaInformeCsv;
use App\Filament\Resources\Incidentes\IncidenteResource;
use App\Models\Incidente;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;


class ListIncidentes extends ListRecords
{
    use DescargaInformeCsv;

    protected static string $resource = IncidenteResource::class;

    public function getTitle(): string
    {
        return 'Incidentes y accidentes';
    }


    protected function getHeaderActions(): array
    {
        return [
            Action::make('informe')
                ->label('Descargar informe (CSV)')
                ->icon('heroicon-m-arrow-down-tray')
                ->color('gray')
                ->action(fn () => $this->informe()),
        ];
    }


    public function informe()
    {
        return $this->descargarCsv('informe_incidentes', [
            'Id' => fn (Incidente $i) => $i->id,
            'Fecha' => fn (Incidente $i) => $i->created_at?->format('d/m/Y H:i:s'),
            'Tipo' => fn (Incidente $i) => Incidente::tipos()[$i->tipo] ?? $i->tipo,
            'Gravedad' => fn (Incidente $i) => $i->severidad,
            'Descripción' => fn (Incidente $i) => $i->descripcion,
            'Usuario' => fn (Incidente $i) => $i->actor_nombre,
            'IP' => fn (Incidente $i) => $i->ip,
            'MAC' => fn (Incidente $i) => $i->mac,
            'Estado' => fn (Incidente $i) => Incidente::estados()[$i->estado] ?? $i->estado,
            'Notas' => fn (Incidente $i) => $i->notas,
            'Cerrado el' => fn (Incidente $i) => $i->resuelto_en?->format('d/m/Y H:i:s'),
            'Auditoría origen' => fn (Incidente $i) => $i->auditoria_id,
            'Detalle' => fn (Incidente $i) => $i->detalle,
        ]);
    }
}
