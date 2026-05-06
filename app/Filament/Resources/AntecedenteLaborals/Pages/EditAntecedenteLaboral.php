<?php

namespace App\Filament\Resources\AntecedenteLaborals\Pages;

use App\Filament\Resources\AntecedenteLaborals\AntecedenteLaboralResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAntecedenteLaboral extends EditRecord
{
    protected static string $resource = AntecedenteLaboralResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
