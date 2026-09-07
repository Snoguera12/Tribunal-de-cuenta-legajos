<?php

namespace App\Filament\Resources\Legajos\Pages;
use App\Filament\Resources\Legajos\LegajoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

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
            CreateAction::make(),
        ];
    }
}
