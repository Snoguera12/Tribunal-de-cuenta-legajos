<?php

namespace App\Filament\Resources\Titulos\Pages;

use App\Filament\Resources\Titulos\TituloResource;
use App\Models\EstudioTitulo;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTitulos extends ListRecords
{
    protected static string $resource = TituloResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pendientes')
            ->label('Títulos pendientes')
            ->icon('heroicon-m-exclamation-triangle')
            ->color('warning')
            ->badge(fn () => EstudioTitulo::pendientes()->count() ?: null)
            ->url(fn () => TituloResource::getUrl('pendientes')),

            CreateAction::make(),
        ];
    }
}
