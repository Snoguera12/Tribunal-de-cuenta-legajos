<?php

namespace App\Filament\Resources\Documentos\Widgets;

use App\Models\Documento;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DocumentosPendientesWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return auth()->user()->isStaffRoles();
    }
    protected function getStats(): array
    {
        $total = Documento::query()
        ->whereNull('descripcion')
        ->orWhereNull('tipodoc')
        ->orWhereNull('legajo_id')->count();

        $resultado = $total == 0 ?
        [
            Stat::make('Documentos pendientes', $total)
            ->description('No hay documentos pendientes.')
            ->color('primary'),
        ]
        :
        [
            Stat::make('Documentos pendientes', $total)
            ->description('Falta revisar documentos.')
            ->url('/legajos/documentos/')
            ->color('primary'),
        ];
        return $resultado;
    }
}
