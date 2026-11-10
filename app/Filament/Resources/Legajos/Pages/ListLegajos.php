<?php

namespace App\Filament\Resources\Legajos\Pages;

use App\Filament\Services\Exports\LegajoExporter;
use App\Models\Legajo;
use App\Filament\Resources\Legajos\LegajoResource;
use App\Filament\Resources\Licencias\LicenciaResource;
use App\Models\Licencia;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListLegajos extends ListRecords
{
    public ?string $activeTab = 'alta';
    protected static string $resource = LegajoResource::class;
    protected function getHeaderWidgets(): array
    {
        return [
            //LegajoWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sin_datos')
            ->label('Legajos sin datos')
            ->icon(Heroicon::ExclamationTriangle)
            ->color('warning')
            ->badge(fn () => Legajo::sinDatos()->count())
            ->visible(fn ($livewire) => ! ($livewire->getTableFilterState('sin_datos')['isActive'] ?? false)
    && Legajo::sinDatos()->exists())
            ->url(fn () => LegajoResource::getUrl('index', [
                'filters' => [
                    'sin_datos' => ['isActive' => true],
                    'estado' => ['value' => ''], // para que también salgan los dados de baja
                ],
            ])),
            // Lleva al listado de licencias registradas por el Sistema de Licencias
            Action::make('licencias')
                ->label('Licencias Aprobadas')
                ->icon(Heroicon::CalendarDays)
                ->color('gray')
                ->url(fn () => LicenciaResource::getUrl('index'))
                ->visible(fn () => auth()->user()?->can('viewAny', Licencia::class) ?? false),

            // Informe de la lista de legajos en Excel/CSV (el empleado no lo ve)
            ExportAction::make()
                ->label('Exportar')
                ->exporter(LegajoExporter::class)
                ->visible(fn () => auth()->user()?->isStaffRoles() ?? false),

            CreateAction::make(),
        ];
    }
}
