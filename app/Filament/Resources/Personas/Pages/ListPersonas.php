<?php

namespace App\Filament\Resources\Personas\Pages;

use App\Filament\Resources\Personas\PersonaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPersonas extends ListRecords
{
    protected static string $resource = PersonaResource::class;
    protected function getHeaderWidgets(): array
    {
        return [
            //PruebaWidget::class, // Esto lo renderiza arriba de la tabla
            //GeneroWidget::class,
            //PersonaTotalWidget::class,
        ];
    }
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
            ->label('Registrar Persona'),
        ];
    }
}
