<?php

namespace App\Filament\Resources\Idiomas\Pages;

use App\Filament\Resources\Idiomas\IdiomaResource;
use App\Models\PersonaIdioma;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIdiomas extends ListRecords
{
    protected static string $resource = IdiomaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pendientes')
                ->label('Idiomas pendientes')
                ->icon('heroicon-m-exclamation-triangle')
                ->color('warning')
                ->badge(fn () => PersonaIdioma::pendientes()->count() ?: null)
                ->url(fn () => IdiomaResource::getUrl('pendientes')),

            CreateAction::make(),
        ];
    }
}