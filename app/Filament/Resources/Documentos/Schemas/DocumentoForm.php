<?php

namespace App\Filament\Resources\Documentos\Schemas;

use App\Enums\TipodocEnum;
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
            ->searchable()
            //Legajo::join('personas', 'legajos.persona_id', '=', 'personas.id')->selectRaw("legajos.id, CONCAT(personas.nombre, ' ', personas.apellido) as nombre_completo")->pluck('nombre_completo', 'id')
            //->options(Legajo::selectRaw('id, num_legajo')->pluck('num_legajo', 'id'))
            //Legajo::join('personas', 'legajos.persona_id', '=', 'personas.id')->selectRaw("legajos.id, CONCAT(personas.nombre, ' ', personas.apellido, ' (Legajo: ', legajos.num_legajo, ' - DNI: ', personas.dni, ')') as nombre_completo")->pluck('nombre_completo', 'id');
            ->options(Legajo::join('personas', 'legajos.persona_id', '=', 'personas.id')->selectRaw("legajos.id, CONCAT('Legajo: ', ' ',legajos.num_legajo, ' (', personas.nombre, ' ', personas.apellido, ' - DNI: ', personas.dni, ')') as nombre_completo")->pluck('nombre_completo', 'id'))
            ->validationMessages([
                'required' => 'Requiere asociar a un Legajo.',
            ])
            ->extraInputAttributes([
                'oninvalid' => "this.setCustomValidity('Requiere asociar a un Legajo.')",
                'oninput' => "this.setCustomValidity('')",
            ]),

            FileUpload::make('ruta')
            ->label('Documento')
            ->directory('documentos') // subdirectorio dentro del disk
            ->disk('public') // o el disk que uses
            ->visibility('public')
            ->preserveFilenames()
            ->visible(fn (string $operation): bool => $operation === 'create')
            ->required(),

            Select::make('tipodoc')
            ->label('Tipo de Documento.')
            ->options(TipodocEnum::class),
            
            Textarea::make('descripcion')
            ->label('Descripción')
            ->columnSpanFull(),
        ]);
    }
}
