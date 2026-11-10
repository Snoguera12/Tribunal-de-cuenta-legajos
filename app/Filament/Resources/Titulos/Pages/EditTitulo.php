<?php

namespace App\Filament\Resources\Titulos\Pages;

use App\Filament\Resources\Titulos\TituloResource;
use Filament\Resources\Pages\EditRecord;

class EditTitulo extends EditRecord
{
    protected static string $resource = TituloResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
