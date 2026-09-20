<?php

namespace App\Filament\Resources\Personas\Pages;


use App\Filament\Resources\Personas\PersonaResource;

use App\Filament\Services\Exports\PersonaExporter;
use App\Filament\Services\Imports\PersonaImporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;

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
            
            ImportAction::make()
            ->label('Importar')
            ->visible(auth()->user()->isAdmin_RRHH())
            ->importer(PersonaImporter::class),

            ExportAction::make()
            ->label('Exportar')
            ->exporter(PersonaExporter::class),

            CreateAction::make()
            ->label('Registrar Persona'),
        ];
    }
}
