<?php

namespace App\Filament\Resources\Legajos\Schemas;

use App\Models\Documento;
use App\Models\Legajo;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LegajoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del legajo')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema(Legajo::getOutSchema('list')),

                Section::make('Documentos adjuntos')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('documentos')
                            ->hiddenLabel()
                            ->placeholder('Sin documentos')
                            ->columns(3)
                            ->grid(1)
                            ->schema(Documento::getOutSchema('list')),
                    ]),
            ]);
    }
}