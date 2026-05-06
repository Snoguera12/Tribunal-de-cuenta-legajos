<?php

namespace App\Filament\Resources\Legajos\Pages;

use App\Filament\Resources\Legajos\LegajoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLegajos extends ListRecords
{
    protected static string $resource = LegajoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
