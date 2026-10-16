<?php

namespace App\Models;

use App\Enums\TipodocEnum;
use App\Models\Concerns\RegistraAuditoria;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\URL;

class Documento extends Model
{
    use SoftDeletes;
    use RegistraAuditoria;

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
            ->options(Legajo::opcionesConPersona())
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
                ->acceptedFileTypes(['application/pdf', 'image/jpeg'])
                ->rules([new \App\Rules\ArchivoPdfOJpeg()])
                ->saveUploadedFileUsing(fn ($file) => \App\Services\ArchivoSeguro::guardar($file, 'documentos'))
                ->validationMessages(['required' => 'Debe subir un archivo válido.'])
                ->required(),
            ];
        } else{
            $esEmpleado = auth()->user()?->isEmpleado() ?? false;

            $resultado = [
                Select::make('legajo_id')
                ->label('Número de legajo')
                ->visible($adjunto_legajo)
                // el empleado solo ve su legajo
                ->options(fn () => Legajo::opcionesConPersona($esEmpleado ? auth()->user()->persona_id : null))
                ->default(fn () => $esEmpleado
                    ? Legajo::where('persona_id', auth()->user()->persona_id)->value('id')
                    : null)
                ->disabled($esEmpleado)
                // disabled no envia el valor, por eso dehydrated(true)
                ->dehydrated(true)
                ->searchable(! $esEmpleado)
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
                ->rules([new \App\Rules\ArchivoPdfOJpeg()])
                ->saveUploadedFileUsing(fn ($file) => \App\Services\ArchivoSeguro::guardar($file, 'documentos'))
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
                ->formatStateUsing(fn ($record) => auth()->user()->isEmpleado()
                    ? 'Documento cargado (sin acceso de descarga)'
                    : 'Abrir archivo')
                ->url(function ($record): ?string {
                    // el empleado no tiene link
                    if (auth()->user()->isEmpleado()) {
                        return null;
                    }
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
            'list' => [
                TextEntry::make('descripcion')
                ->label('Descripción'),

                TextEntry::make('tipodoc')
                ->label('Tipo'),

                TextEntry::make('ruta')
                ->label('Documento')
                ->hiddenLabel()
                ->bulleted()
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->formatStateUsing(fn ($record) => auth()->user()->isEmpleado()
                    ? 'Documento cargado (sin acceso de descarga)'
                    : ($record->ruta ?? '—'))
                ->openUrlInNewTab()
                ->url(function ($record): ?string {
                    if (auth()->user()->isEmpleado()) {
                        return null;
                    }
                    if (!$record->ruta) return null;
                    
                    return URL::temporarySignedRoute(
                        'documentos.ver',
                        now()->addMinutes(5),
                        [
                            'path' => $record->ruta,
                            'legajo_id' => $record->legajo_id,
                        ]
                    );
                }),
            ],
        };

        return $resultado;
    }


    // pendiente = le falta la descripcion, el tipo o el legajo
    public function scopePendientes($consulta)
    {
        return $consulta->where(fn ($q) => $q->whereNull('descripcion')
            ->orWhereNull('tipodoc')
            ->orWhereNull('legajo_id'));
    }
}
