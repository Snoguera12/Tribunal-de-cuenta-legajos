<?php

namespace App\Filament\Resources\Oficinas\Pages;

use App\Filament\Resources\Oficinas\OficinaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOficinas extends ListRecords
{
    protected static string $resource = OficinaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
