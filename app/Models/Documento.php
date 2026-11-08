<?php

namespace App\Models;

use App\Enums\TipodocEnum;
use App\Filament\Forms\Components\ArchivoDocumento;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Legajo;

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
    ];
    public function legajos(): BelongsToMany
    {
        return $this->belongsToMany(Legajo::class, 'documento_legajo')->withTimestamps();
    }
    public function licencia(){ return $this->hasOne(Licencia::class); }
    private static function etiquetaLegajo(Legajo $legajo): string
    {
        return 'Legajo ' . $legajo->num_legajo . ' (' . $legajo->persona?->nombre . ' ' . $legajo->persona?->apellido . ' - DNI: ' . $legajo->persona?->dni . ')';
    }
    public function scopePendientes(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('descripcion')
                ->orWhereNull('tipodoc')
                ->orDoesntHave('legajos');
        });
    }
    public static function nombreAleatorio(TemporaryUploadedFile $file): string
    {
        return Str::random(40) . '.' . strtolower($file->getClientOriginalExtension());
    }
    public static function abrirDocumento($record): ?string{
        if (! $record->ruta) {
            return null;
        }

        return URL::temporarySignedRoute(
            'documentos.ver',
            now()->addMinutes(self::$TIEMPO_ACCESO),
            ['documento' => $record->id]
        );
    }
    public static function nombreVisible($record): string
    {
        return $record->nombre_original ?: ($record->descripcion ?: basename((string) $record->ruta));
    }
    public static function getFromSchema(bool $solo_archivos, bool $adjunto_legajo) : array{

        if($solo_archivos){
            $resultado = [
                ArchivoDocumento::make('ruta')
                ->label('Documento Adjunto')
                ->multiple()
                ->openable()
                ->required(),
            ];
        } else{
            $resultado = [
                Select::make('legajos')
                ->label('Legajos')
                ->visible($adjunto_legajo)
                ->relationship(
                    name: 'legajos',
                    titleAttribute: 'num_legajo',
                    modifyQueryUsing: fn (Builder $query) => $query->where('legajos.estado', true),
                )
                ->getOptionLabelFromRecordUsing(fn (Legajo $legajo) => "{$legajo->persona?->nombre} {$legajo->persona?->apellido} (DNI: {$legajo->persona?->dni}) - Legajo {$legajo->num_legajo}")
                ->multiple()
                ->searchable()
                ->preload()
                ->required(),

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
                
                ArchivoDocumento::make('ruta')
                ->label('Documento Adjunto')
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