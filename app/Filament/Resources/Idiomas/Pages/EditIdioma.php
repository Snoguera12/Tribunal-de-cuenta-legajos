<?php

namespace App\Filament\Resources\Idiomas\Pages;

use App\Filament\Resources\Idiomas\IdiomaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIdioma extends EditRecord
{
    protected static string $resource = IdiomaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
