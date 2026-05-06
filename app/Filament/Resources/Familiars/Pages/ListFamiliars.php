<?php

namespace App\Filament\Resources\Familiars\Pages;

use App\Filament\Resources\Familiars\FamiliarResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFamiliars extends ListRecords
{
    protected static string $resource = FamiliarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
