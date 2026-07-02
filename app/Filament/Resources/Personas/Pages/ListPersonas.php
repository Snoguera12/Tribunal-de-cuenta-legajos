<?php

namespace App\Filament\Resources\Personas\Pages;

use App\Filament\Exports\PersonaExporter;
use App\Filament\Imports\PersonaImporter;
use App\Filament\Resources\Personas\PersonaResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Forms\Components\FileUpload;
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
            ImportAction::make()->label('Importar')->importer(PersonaImporter::class)->visible(auth()->user()->isAdmin_RRHH()),
            ExportAction::make()->label('Exportar')->exporter(PersonaExporter::class)->visible(auth()->user()->isAdmin_RRHH()),
            CreateAction::make()->label('Registrar Persona'),
        ];
    }
}
