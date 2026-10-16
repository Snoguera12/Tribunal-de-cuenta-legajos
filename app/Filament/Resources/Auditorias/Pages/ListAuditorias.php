<?php

namespace App\Filament\Resources\Auditorias\Pages;

<<<<<<< HEAD
use App\Filament\Resources\Auditorias\AuditoriaResource;
use Filament\Resources\Pages\ListRecords;

class ListAuditorias extends ListRecords
{
    protected static string $resource = AuditoriaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // sin boton de crear
        ];
    }
=======
use App\Filament\Concerns\DescargaInformeCsv;
use App\Filament\Resources\Auditorias\AuditoriaResource;
use App\Models\Auditoria;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;


class ListAuditorias extends ListRecords
{
    use DescargaInformeCsv;

    protected static string $resource = AuditoriaResource::class;

    public function getTitle(): string
    {
        return 'Auditoría del sistema';
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
        return $this->descargarCsv('informe_auditoria', [
            'Id' => fn (Auditoria $a) => $a->id,
            'Fecha' => fn (Auditoria $a) => $a->created_at?->format('d/m/Y H:i:s'),
            'Nivel' => fn (Auditoria $a) => $a->severidad,
            'Categoría' => fn (Auditoria $a) => $a->categoria,
            'Evento' => fn (Auditoria $a) => $a->evento,
            'Actor' => fn (Auditoria $a) => $a->actor_nombre,
            'Rol' => fn (Auditoria $a) => $a->actor_rol,
            'Descripción' => fn (Auditoria $a) => $a->descripcion,
            'Registro' => fn (Auditoria $a) => $a->modelo ? $a->modelo . ' #' . $a->modelo_id : '',
            'IP' => fn (Auditoria $a) => $a->ip,
            'MAC' => fn (Auditoria $a) => $a->mac,
            'Equipo / navegador' => fn (Auditoria $a) => $a->sistema_operativo,
            'Dispositivo' => fn (Auditoria $a) => $a->dispositivo,
            'Página' => fn (Auditoria $a) => $a->url,
            'Método' => fn (Auditoria $a) => $a->metodo,
            'Valores anteriores' => fn (Auditoria $a) => $a->valores_anteriores,
            'Valores nuevos' => fn (Auditoria $a) => $a->valores_nuevos,
            'Extra' => fn (Auditoria $a) => $a->extra,
            'Hash' => fn (Auditoria $a) => $a->hash,
        ]);
    }
>>>>>>> version2-20261014_210855
}
