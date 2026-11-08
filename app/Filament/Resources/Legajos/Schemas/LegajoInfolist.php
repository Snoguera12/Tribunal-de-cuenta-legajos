<?php

namespace App\Filament\Resources\Legajos\Schemas;

use App\Filament\Resources\Legajos\Pages\ViewLegajo;
use App\Filament\Resources\Legajos\RelationManagers\DocumentosRelationManager;
use App\Filament\Resources\Legajos\RelationManagers\LicenciasRelationManager;
use App\Models\Legajo;
use App\Models\Persona;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class LegajoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tabs_base')
                    ->columnSpanFull()
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('Tab Legajo')
                            ->label('Legajo')
                            ->id('legajo')
                            ->key('legajo')
                            ->icon('heroicon-m-folder-open')
                            ->columns(3)
                            ->schema(Legajo::getOutSchema('list')),

                        Tab::make('Tab Persona')
                            ->label('Persona')
                            ->id('persona')
                            ->key('persona')
                            ->icon('heroicon-m-identification')
                            ->schema([
                                Grid::make(4)
                                ->relationship('persona')
                                ->schema(Persona::getOutSchema('list')),
                            ]),

                        Tab::make('Tab Documentos')
                            ->label('Documentos')
                            ->id('documentos')
                            ->key('documentos')
                            ->icon('heroicon-m-document-text')
                            ->schema([
                                Livewire::make(DocumentosRelationManager::class, fn ($record) => [
                                    'ownerRecord' => $record,
                                    'pageClass' => ViewLegajo::class,
                                ])->key('documentos-rm'),
                            ]),

                        Tab::make('Tab Licencias')
                            ->label('Licencias')
                            ->id('licencias')
                            ->key('licencias')
                            ->icon('heroicon-m-clipboard-document-check')
                            ->schema([
                                Livewire::make(LicenciasRelationManager::class, fn ($record) => [
                                    'ownerRecord' => $record,
                                    'pageClass' => ViewLegajo::class,
                                ])->key('licencias-rm'),
                            ]),
                    ]),
            ]);
    }
}