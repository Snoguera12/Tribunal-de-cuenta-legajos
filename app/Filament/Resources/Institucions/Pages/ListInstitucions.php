<?php

namespace App\Filament\Resources\Institucions\Pages;

use App\Filament\Resources\Institucions\InstitucionResource;
use App\Models\Curso;
use App\Models\Estudio;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInstitucions extends ListRecords
{
    protected static string $resource = InstitucionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pendientes')
            ->label('Instituciones pendientes')
            ->icon('heroicon-m-exclamation-triangle')
            ->color('warning')
            ->badge(fn () => (Estudio::pendientes()->count() + Curso::pendientes()->count()) ?: null)
            ->url(fn () => InstitucionResource::getUrl('pendientes')),
            
            CreateAction::make(),
        ];
    }
}
