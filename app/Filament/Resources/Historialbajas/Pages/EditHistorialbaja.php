<?php

namespace App\Filament\Resources\Historialbajas\Pages;

use App\Filament\Resources\Historialbajas\HistorialbajaResource;
use Filament\Resources\Pages\EditRecord;

class EditHistorialbaja extends EditRecord
{
    protected static string $resource = HistorialbajaResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
