<?php

namespace App\Filament\Resources\Personas\Pages;

use App\Filament\Resources\Personas\PersonaResource;
use App\Filament\Resources\Personas\Widgets\GeneroWidget;
use App\Filament\Widgets\PruebaWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPersonas extends ListRecords
{
    protected static string $resource = PersonaResource::class;
    protected function getHeaderWidgets(): array
    {
        return [
            PruebaWidget::class, // Esto lo renderiza arriba de la tabla
            GeneroWidget::class,
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
