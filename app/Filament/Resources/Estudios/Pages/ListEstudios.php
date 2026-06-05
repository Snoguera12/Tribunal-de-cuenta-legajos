<?php

namespace App\Filament\Resources\Estudios\Pages;

use App\Filament\Resources\Estudios\EstudioResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEstudios extends ListRecords
{
    protected static string $resource = EstudioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
