<?php

namespace App\Models;

use App\Enums\TipodocEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class Documento extends Model
{
    protected $casts = [
        'tipodoc' => TipodocEnum::class,
    ];

    protected $fillable = [
        'ruta',
        'nombre_original',
        'descripcion',
        'tipodoc',
        'legajo_id',
    ];

    public function legajo(){
        return $this->belongsTo(Legajo::class, 'legajo_id');
    }
    public static function getFromSchemaRevisar() : array{
        $resultado = [
            Grid::make([
                'default' => 1,
                'sm' => 2,
            ])->schema([
            Select::make('legajo_id')
            ->label('Número de legajo')
            ->searchable()
            ->columns(1)
            ->options(Legajo::join('personas', 'legajos.persona_id', '=', 'personas.id')->selectRaw("legajos.id, CONCAT('Legajo: ', ' ',legajos.num_legajo, ' (', personas.nombre, ' ', personas.apellido, ' - DNI: ', personas.dni, ')') as nombre_completo")->pluck('nombre_completo', 'id'))
            ->validationMessages([
                'required' => 'Requiere asociar a un Legajo.',
            ])
            ->extraInputAttributes([
                'oninvalid' => "this.setCustomValidity('Requiere asociar a un Legajo.')",
                'oninput' => "this.setCustomValidity('')",
            ]),

            Textarea::make('descripcion')
            ->label('Descripción / Notas')
            ->placeholder('Ej. Copia certificada del título...')
            ->required(),

            Select::make('tipodoc')
            ->label('Tipo de Documento')
            ->required()
            ->native(false)
            ->options(TipodocEnum::class)
            ->validationMessages(['required' => 'Seleccione el tipo de documento.']),
            ]),
            
        ];
        return $resultado;
    }
    public static function getFromSchema(bool $solo_archivos, bool $adjunto_legajo) : array{

        if($solo_archivos){
            $resultado = [
                FileUpload::make('ruta')
                ->label('Documento Adjunto')
                ->multiple()
                ->disk('local')
                ->visibility('private')
                ->directory('documentos/')
                ->maxSize(10240)
                ->openable()
                ->acceptedFileTypes(['application/pdf', 'image/jpeg'])
                ->rules(['mimes:pdf,jpg,jpeg'])
                ->validationMessages(['required' => 'Debe subir un archivo válido.'])
                ->required(),
            ];
        } else{
            $resultado = [
                Select::make('legajo_id')
                ->label('Número de legajo')
                ->visible($adjunto_legajo)
                ->searchable()
                ->options(Legajo::join('personas', 'legajos.persona_id', '=', 'personas.id')->selectRaw("legajos.id, CONCAT('Legajo: ', ' ',legajos.num_legajo, ' (', personas.nombre, ' ', personas.apellido, ' - DNI: ', personas.dni, ')') as nombre_completo")->pluck('nombre_completo', 'id'))
                ->validationMessages([
                    'required' => 'Requiere asociar a un Legajo.',
                ])
                ->extraInputAttributes([
                    'oninvalid' => "this.setCustomValidity('Requiere asociar a un Legajo.')",
                    'oninput' => "this.setCustomValidity('')",
                ]),

                Textarea::make('descripcion')
                ->label('Descripción / Notas')
                ->placeholder('Ej. Copia certificada del título...')
                ->required()
                ->rows(2),

                Select::make('tipodoc')
                ->label('Tipo de Documento')
                ->required()
                ->native(false)
                ->options(TipodocEnum::class)
                ->validationMessages(['required' => 'Seleccione el tipo de documento.']),
                
                FileUpload::make('ruta')
                ->label('Documento Adjunto')
                ->disk('local')
                ->visibility('private')
                ->directory('documentos/')
                ->maxSize(10240)
                ->acceptedFileTypes(['application/pdf', 'image/jpeg'])
                ->rules(['mimes:pdf,jpg,jpeg'])
                ->validationMessages(['required' => 'Debe subir un archivo válido.'])
                ->required()
                ->deletable(false),
            ];
        }
        
        return $resultado;
    }

    public static function getOutSchema(string $mode_out) : array{
        $resultado = match($mode_out){
            'table' => [
                TextColumn::make('legajo.num_legajo')
                ->label("Número de legajo")
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('ruta')
                ->label('Documento')
                //->formatStateUsing(fn () => 'Abrir Archivo')
                ->url(function ($record): ?string {
                    if (!$record->ruta) return null;
                    
                    return URL::temporarySignedRoute(
                        'documentos.ver',
                        now()->addMinutes(5),
                        [
                            'path' => $record->ruta,
                            'legajo_id' => $record->legajo_id
                        ]
                    );
                })
                ->openUrlInNewTab()
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('tipodoc')
                ->label('Tipo de Documento')
                ->sortable()
                ->openUrlInNewTab()
                ->toggleable(isToggledHiddenByDefault: false),
            ],
            'folist' => [],
        };

        return $resultado;
    }
    protected static function booted()
    {
        static::creating(function (Documento $documento) {
            $ruta = storage_path('app/private/documentos/' . $documento->archivo);

            if (file_exists($ruta)) {
                $mime = mime_content_type($ruta);

                if (! in_array($mime, ['application/pdf', 'image/jpeg'], true)) {
                    throw new \Exception('El archivo subido no es un PDF ni una imagen JPG/JPEG válida.');
                }
            }
        });
    }
}
