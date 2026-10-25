<?php

namespace App\Filament\Resources\Tokens\Pages;

use App\Enums\TokenDuracion;
use App\Filament\Resources\Tokens\TokenResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTokens extends ListRecords
{
    protected static string $resource = TokenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
