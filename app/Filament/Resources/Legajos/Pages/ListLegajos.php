<?php

namespace App\Filament\Resources\Legajos\Pages;
use App\Filament\Resources\Legajos\LegajoResource;
use App\Filament\Resources\Legajos\Widgets\LegajoWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListLegajos extends ListRecords
{
    public ?string $activeTab = 'alta';
    protected static string $resource = LegajoResource::class;
    protected function getHeaderWidgets(): array
    {
        return [
            LegajoWidget::class,
        ];
    }
    public function getTabs(): array
    {
        return [
            // Botón "Todos": No aplica ningún filtro a la consulta
            'Todos' => Tab::make(),

            // Botón "Alta": Filtra donde el estado sea 'alta'
            'alta' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('estado', 1)),

            // Botón "Baja": Filtra donde el estado sea 'baja'
            'baja' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('estado', 0)),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
