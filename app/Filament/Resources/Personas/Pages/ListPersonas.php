<?php

namespace App\Filament\Resources\Personas\Pages;


use App\Filament\Resources\Personas\PersonaResource;

use App\Filament\Services\Exports\PersonaExporter;
use App\Filament\Services\Imports\PersonaImporter;
use App\Models\Auditoria;
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
            ->visible(fn () => auth()->user()?->isAdmin_RRHH() ?? false)
            ->importer(PersonaImporter::class)
            ->after(fn () => Auditoria::registrar(
                evento: 'importacion',
                descripcion: 'Inició la importación de personas desde un archivo',
                severidad: 'aviso',
            )),

            // los datos personales salen del sistema: solo el personal de gestión y queda anotado quién los pidió
            ExportAction::make()
            ->label('Exportar')
            ->visible(fn () => auth()->user()?->isStaffRoles() ?? false)
            ->exporter(PersonaExporter::class)
            ->after(fn () => Auditoria::registrar(
                evento: 'exportacion',
                descripcion: 'Pidió exportar el listado de personas',
                severidad: 'aviso',
            )),

            CreateAction::make()
            ->label('Registrar Persona'),
        ];
    }
}
