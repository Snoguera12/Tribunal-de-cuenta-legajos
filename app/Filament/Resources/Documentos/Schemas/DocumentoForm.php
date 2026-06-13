<?php

namespace App\Filament\Resources\Documentos\Schemas;

use App\Models\Legajo;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;


class DocumentoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('legajo_id')
                    ->label('Número de legajo')
                    ->required()
                    ->searchable()
                    ->options(Legajo::selectRaw('id, num_legajo')->pluck('num_legajo', 'id'))
                    ->validationMessages([
                        'required' => 'Requiere asociar a un Legajo.',
                    ])
                    ->extraInputAttributes([
                        'oninvalid' => "this.setCustomValidity('Requiere asociar a un Legajo.')",
                        'oninput' => "this.setCustomValidity('')",
                    ]),
                FileUpload::make('archivo')
                    ->label('Documento Adjunto')
                    ->disk('public') // Disco de almacenamiento (config/filesystems.php)
                    ->directory('documentos/') // Carpeta destino dentro del disco
                    ->visibility('public') // Visibilidad del archivo
                    ->acceptedFileTypes(['application/pdf', 'image/*']) // Restringir formatos
                    ->maxSize(10240) // Tamaño máximo en KB (10 MB)
                    ->required(),
                Textarea::make('descripcion')
                    ->label('Descripción')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('tipodoc')
                    ->label('Tipo de Documento.')
                    ->required()
                    ->numeric(),
                Toggle::make('activo'),
                DateTimePicker::make('fecha_de_creacion')
                    ->label('Fecha de Creación.')
                    ->native(false)
                    ->helperText('Si no introduce la fecha de creación, se asigna la fecha de hoy.'),
            ]);
    }
}
