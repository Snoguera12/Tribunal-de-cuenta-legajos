<?php

namespace App\Filament\Resources\Legajos\Pages;
use App\Filament\Resources\Legajos\LegajoResource;
use App\Filament\Resources\Licencias\LicenciaResource;
use App\Models\Licencia;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
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
            // Lleva al listado de licencias registradas por el Sistema de Licencias
            Action::make('licencias')
                ->label('Licencias Aprobadas')
                ->icon(Heroicon::CalendarDays)
                ->color('gray')
                ->url(fn () => LicenciaResource::getUrl('index'))
                ->visible(fn () => auth()->user()?->can('viewAny', Licencia::class) ?? false),

            CreateAction::make(),
        ];
    }
}
