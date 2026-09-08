<?php

namespace App\Filament\Resources\Legajos\Schemas;

use App\Enums\TipoContratoEnum;
use App\Enums\TipodocEnum;
use App\Models\Area;
use App\Models\Cargo;
use App\Models\Categoria;
use App\Models\Documento;
use App\Models\Legajo;
use App\Models\Persona;
use Carbon\Carbon;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class LegajoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
        ->components([
            Section::make()
            ->columns(3)
            ->columnSpanFull()
            ->schema(Legajo::getFormSchema(true)),

            Section::make('Documentación Digitalizada')
            ->description('Cargue los archivos adjuntos y documentos que respaldan este legajo.')
            ->icon('heroicon-o-document-arrow-up')
            ->collapsible() // El usuario puede ocultar la zona de archivos si no la necesita en el momento
            ->columnSpanFull()
            ->extraAttributes([
                // Forzamos un borde más oscuro y fondo claro para que resalte del contenedor de Legajos
                'style' => '
                    border: 2px solid #2e3032 !important; 
                    border-radius: 12px !important; 
                    background-color: #f9fafb !important;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.05) !important;
                '
            ])
            ->schema([
                Repeater::make('Documento')
                ->relationship('Documentos')
                ->hiddenLabel()
                ->columnSpanFull()
                ->addActionLabel('Adjuntar un Documento')
                ->grid(3)
                ->schema([
                    Section::make()
                    ->columns(1)
                    ->schema([
                        Grid::make(1)
                        ->columnSpan(1)
                        ->schema(Documento::getFromSchema(false, false)),
                    ]),
                ]),
            ]),
        ]);
    }
}

/*
Tabs::make('Tabs_Base')
            ->columnSpanFull()
            ->tabs([
                Tab::make('Tab Legajo')
                ->label('Legajo')
                ->columns(2)
                ->schema([
                    
                
                Section::make('Documentación Digitalizada')
                ->description('Cargue los archivos adjuntos y documentos que respaldan este legajo.')
                ->icon('heroicon-o-document-arrow-up')
                ->collapsible() // El usuario puede ocultar la zona de archivos si no la necesita en el momento
                ->columnSpanFull()
                ->extraAttributes([
                    // Forzamos un borde más oscuro y fondo claro para que resalte del contenedor de Legajos
                    'style' => '
                        border: 2px solid #2e3032 !important; 
                        border-radius: 12px !important; 
                        background-color: #f9fafb !important;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.05) !important;
                    '
                ])
                ->schema([
                    Repeater::make('Documento')
                    ->relationship('Documentos')
                    ->hiddenLabel()
                    ->columnSpanFull()
                    ->addActionLabel('Adjuntar un Documento')
                    ->grid(2) // Mantiene tus dos columnas de documentos lado a lado
                    ->schema([
                        // SOLUCIÓN NATIVA: Usamos un Fieldset o una Section interna. 
                        // Cada vez que se crea un documento, Filament genera este recuadro contenedor.
                        Section::make()
                        ->columns(1) // Distribuye el contenido internamente en 2 columnas
                        ->schema([
                            // Columna Izquierda: Metadatos del documento
                            Grid::make(1)
                            ->columnSpan(1)
                            ->schema(Documento::getFromSchema(false, false)),
                        ]),
                    ]),
                ]),

            ]),
*/