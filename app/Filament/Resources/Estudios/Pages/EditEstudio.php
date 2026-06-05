<?php

namespace App\Filament\Resources\Estudios\Pages;

use App\Filament\Resources\Estudios\EstudioResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEstudio extends EditRecord
{
    protected static string $resource = EstudioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
