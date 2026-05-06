<?php

namespace App\Filament\Resources\Familiars\Pages;

use App\Filament\Resources\Familiars\FamiliarResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFamiliar extends EditRecord
{
    protected static string $resource = FamiliarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
