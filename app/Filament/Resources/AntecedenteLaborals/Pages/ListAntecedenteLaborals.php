<?php

namespace App\Filament\Resources\AntecedenteLaborals\Pages;

use App\Filament\Resources\AntecedenteLaborals\AntecedenteLaboralResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAntecedenteLaborals extends ListRecords
{
    protected static string $resource = AntecedenteLaboralResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
