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
        $total = Documento::pendientes()->count();

        $resultado = $total == 0 ?
        [
            Stat::make('Documentos pendientes', $total)
            ->description('No hay documentos pendientes.')
            ->descriptionIcon('heroicon-m-check-circle')
            ->color('success'),
        ]
        :
        [
            Stat::make('Documentos pendientes', $total)
            ->description('Les falta el legajo, el tipo o la descripción.')
            ->descriptionIcon('heroicon-m-document-magnifying-glass')
            ->url(\App\Filament\Resources\Documentos\DocumentoResource::getUrl('index'))
            ->color('warning'),
        ];
        return $resultado;
    }
}
