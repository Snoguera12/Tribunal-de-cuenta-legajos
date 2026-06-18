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
                Select::make('tipodoc')
                    ->label('Tipo de Documento.')
                    ->required()
                    ->options([
                        0 => 'DNI',
                        1 => 'Título',
                        2 => 'Cursos',
                        3 => 'Liciancia',
                        4 => 'Acta de Nacimiento',
                        5 => 'Certificado de Escolaridad',
                        6 => 'Certificado Defunción',
                        7 => 'Certificado de Casamiento',
                        8 => 'Sumario',
                        9 => 'Resolución',
                        10 => 'Foto de Perfil',
                        11 => 'Curriculum',
                        12 => 'Otro',
                    ]),
                Toggle::make('activo'),
                DateTimePicker::make('fecha_de_creacion')
                    ->label('Fecha de Creación.')
                    ->native(false)
                    ->helperText('Si no introduce la fecha de creación, se asigna la fecha de hoy.'),
            ]);
    }
}
