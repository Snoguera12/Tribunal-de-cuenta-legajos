<?php

namespace App\Filament\Resources\Documentos\Pages;

use App\Filament\Resources\Documentos\Pages\RevisarDocumentos;
use App\Filament\Resources\Documentos\Pages\SubirDocumentos;
use App\Filament\Resources\Documentos\DocumentoResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDocumentos extends ListRecords
{
    protected static string $resource = DocumentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('revisarDocumentos')
            ->label('Revisar documentos')
            ->icon('heroicon-o-exclamation-triangle')
            ->color('gray')
            ->url(RevisarDocumentos::getUrl()),
            
            Action::make('subirDocumentos')
            ->label('Subir documentos')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('gray')
            ->url(SubirDocumentos::getUrl()),

            CreateAction::make(),
        ];
    }
}
