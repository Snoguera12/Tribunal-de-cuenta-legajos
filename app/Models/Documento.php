<?php

namespace App\Models;

use App\Enums\TipodocEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class Documento extends Model
{
    private static $TIEMPO_ACCESO = 30;
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
    public function legajos(): BelongsToMany
    {
        return $this->belongsToMany(Legajo::class, 'documento_legajo')->withTimestamps();
    }
    public function licencia(){ return $this->hasOne(Licencia::class); }
    public static function nombreAleatorio(TemporaryUploadedFile $file): string
    {
        return Str::random(40) . '.' . strtolower($file->getClientOriginalExtension());
    }
    public static function abrirDocumento($record): ?string{
        if (!$record->ruta) return null;
        
        return URL::temporarySignedRoute(
            'documentos.ver',
            now()->addMinutes(self::$TIEMPO_ACCESO),
            [
                'path' => $record->ruta,
                'legajo_id' => $record->legajo_id
            ]
        );
    }
    public static function nombreVisible($record): string
    {
        return $record->nombre_original ?: ($record->descripcion ?: basename((string) $record->ruta));
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
                ->getUploadedFileNameForStorageUsing(self::nombreAleatorio(...))
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
                ->getUploadedFileNameForStorageUsing(self::nombreAleatorio(...))
                ->required()
                ->deletable(false),
            ];
        }
        
        return $resultado;
    }

    public static function getOutSchema(string $mode_out) : array{
        $resultado = match($mode_out){
            'table' => [
                TextColumn::make('nombre_original')
                ->label('Documento')
                ->state(self::nombreVisible(...))
                ->limit(50)
                ->searchable(['nombre_original', 'descripcion'])
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->url(self::abrirDocumento(...))
                ->openUrlInNewTab(),

            ],
            'list' => [
                TextEntry::make('nombre_original')
                ->hiddenLabel()
                ->state(self::nombreVisible(...))
                ->limit(50)
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->openUrlInNewTab()
                ->url(self::abrirDocumento(...)),

                TextEntry::make('descripcion')
                ->label('Descripción'),

                TextEntry::make('tipodoc')
                ->label('Tipo'),
            ],
        };

        return $resultado;
    }
}