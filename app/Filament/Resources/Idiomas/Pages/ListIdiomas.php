<?php

namespace App\Filament\Resources\Idiomas\Pages;

use App\Filament\Resources\Idiomas\IdiomaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIdiomas extends ListRecords
{
    protected static string $resource = IdiomaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
