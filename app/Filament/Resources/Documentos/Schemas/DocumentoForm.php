<?php

namespace App\Filament\Resources\Documentos\Schemas;

use App\Enums\TipodocEnum;
use App\Models\Legajo;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;


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
            ->options(Legajo::join('personas', 'legajos.persona_id', '=', 'personas.id')->selectRaw("legajos.id, CONCAT('Legajo: ', ' ',legajos.num_legajo, ' (', personas.nombre, ' ', personas.apellido, ' - DNI: ', personas.dni, ')') as nombre_completo")->pluck('nombre_completo', 'id'))
            ->validationMessages([
                'required' => 'Requiere asociar a un Legajo.',
            ])
            ->extraInputAttributes([
                'oninvalid' => "this.setCustomValidity('Requiere asociar a un Legajo.')",
                'oninput' => "this.setCustomValidity('')",
            ]),
            FileUpload::make('archivo')
            ->label('Documento Adjunto')
            ->disk('local') // antes era 'public', lo cambiamos para que no quede accesible por url directa
            ->directory('documentos')
            ->visibility('private')
            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png']) // sacamos image/* porque permitia subir svg
            ->maxSize(10240) // 10 MB
            ->getUploadedFileNameForStorageUsing(fn ($file) => Str::uuid() . '.' . $file->getClientOriginalExtension()) // asi no se guarda el nombre original del archivo
            ->required(),
            Select::make('tipodoc')
            ->label('Tipo de Documento.')
            ->required()
            ->options(TipodocEnum::class),
            Textarea::make('descripcion')
            ->label('Descripción')
            ->required()
            ->columnSpanFull(),
        ]);
    }
}
