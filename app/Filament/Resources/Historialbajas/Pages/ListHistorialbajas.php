<?php

namespace App\Filament\Resources\Historialbajas\Pages;

use App\Filament\Resources\Historialbajas\HistorialbajaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHistorialbajas extends ListRecords
{
    protected static string $resource = HistorialbajaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //CreateAction::make(),
        ];
    }
}
