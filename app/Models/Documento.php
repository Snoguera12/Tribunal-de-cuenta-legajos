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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
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
        'origen',
        'api_client_id',
        'id_externo',
        'dni_sugerido',
    ];

    // un documento que se carga con legajo queda tambien en la tabla de asignaciones
    protected static function booted(): void
    {
        // un documento compartido con otros legajos no se borra para todos al borrarlo desde uno:
        // solo se saca de ese legajo. El borrado definitivo (forceDelete) sigue siendo del administrador.
        static::deleting(function (Documento $documento) {
            if ($documento->isForceDeleting() || ! $documento->legajo_id) {
                return;
            }

            $asignados = $documento->legajos()->pluck('legajos.id');

            if ($asignados->count() <= 1) {
                return;
            }

            $quitado = $documento->legajo_id;
            $documento->legajos()->detach($quitado);
            $documento->legajo_id = $asignados->reject(fn ($id) => (int) $id === (int) $quitado)->first();
            $documento->save();

            return false;
        });

        static::saved(function (Documento $documento) {
            if ($documento->legajo_id && ($documento->wasChanged('legajo_id') || $documento->wasRecentlyCreated)
                && DB::table('legajos')->where('id', $documento->legajo_id)->exists()) {
                $documento->legajos()->syncWithoutDetaching([$documento->legajo_id]);
            }
        });
    }

    // legajo principal (el primero al que se asigno)
    public function legajo(){
        return $this->belongsTo(Legajo::class, 'legajo_id');
    }

    // todos los legajos que tienen este documento (un solo archivo, sin copias)
    public function legajos(): BelongsToMany
    {
        return $this->belongsToMany(Legajo::class, 'documento_legajo')
            ->withPivot('asignado_por')
            ->withTimestamps();
    }

    public function apiClient()
    {
        return $this->belongsTo(ApiClient::class, 'api_client_id');
    }

    /**
     * Deja el documento asignado exactamente a esos legajos (uno, varios o todos).
     * No se copia el archivo ni se crean documentos repetidos: solo cambian las asignaciones.
     * Con una lista vacia el documento vuelve a quedar pendiente.
     */
    public function asignarA(array $legajoIds, ?int $porUsuarioId = null): void
    {
        $ids = collect($legajoIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();

        DB::transaction(function () use ($ids, $porUsuarioId) {
            $this->legajos()->sync($ids->mapWithKeys(fn ($id) => [$id => ['asignado_por' => $porUsuarioId]])->all());

            $this->legajo_id = $ids->first();
            $this->save();
        });
    }

    // el legajo que indica el DNI de la API (primero uno de alta), para sugerirlo al asignar
    public function legajoSugerido(): ?Legajo
    {
        if (! $this->dni_sugerido) {
            return null;
        }

        return Legajo::query()
            ->whereHas('persona', fn ($q) => $q->where('dni', $this->dni_sugerido))
            ->orderByDesc('estado')
            ->orderByDesc('fecha_de_ingreso')
            ->first();
    }

    // ¿puede esta persona abrir o descargar el CONTENIDO del archivo?
    // funcionario, RRHH y administrador: todos. Empleado: nadie, salvo que se active DOC_EMPLEADO_ABRE_PROPIOS (y solo los suyos)
    public static function puedeAbrirContenido(?User $usuario, ?self $documento): bool
    {
        if (! $usuario) {
            return false;
        }

        if (! $usuario->isEmpleado()) {
            return true;
        }

        return (bool) config('sistema.documentos.empleado_abre_propios')
            && $documento !== null
            && $documento->perteneceAPersona($usuario->persona_id);
    }

    // enlace firmado que vence (5 minutos): para ver en una pestaña o para descargar
    public function urlTemporal(bool $descargar = false, int $minutos = 5): ?string
    {
        if (! $this->ruta) {
            return null;
        }

        return URL::temporarySignedRoute('documentos.ver', now()->addMinutes($minutos), array_filter([
            'path' => $this->ruta,
            'legajo_id' => $this->legajo_id,
            'descargar' => $descargar ? 1 : null,
        ], fn ($valor) => $valor !== null));
    }

    // nombre con el que se guarda al descargar: el original, si no uno armado con el tipo
    public function nombreDescarga(): string
    {
        $extension = strtolower(pathinfo((string) $this->ruta, PATHINFO_EXTENSION)) ?: 'pdf';
        $nombre = $this->nombre_original ?: ('documento-' . ($this->id ?? 'sin-id') . '.' . $extension);

        // solo letras, numeros, punto, guion y guion bajo
        $nombre = preg_replace('/[^A-Za-z0-9._-]+/', '_', \Illuminate\Support\Str::ascii($nombre));
        $nombre = trim($nombre, '._') ?: 'documento';

        return str_ends_with(strtolower($nombre), '.' . $extension) ? $nombre : $nombre . '.' . $extension;
    }

    // ¿lo tiene este legajo, como principal o compartido?
    public function scopeDelLegajo($consulta, int $legajoId)
    {
        return $consulta->where(fn ($q) => $q->where('legajo_id', $legajoId)
            ->orWhereHas('legajos', fn ($l) => $l->where('legajos.id', $legajoId)));
    }

    // ¿es de esta persona (en cualquiera de sus legajos)?
    public function scopeDeLaPersona($consulta, ?int $personaId)
    {
        return $consulta->where(fn ($q) => $q->whereHas('legajo', fn ($l) => $l->where('persona_id', $personaId))
            ->orWhereHas('legajos', fn ($l) => $l->where('legajos.persona_id', $personaId)));
    }

    public function perteneceAPersona(?int $personaId): bool
    {
        if (! $personaId) {
            return false;
        }

        return $this->legajos()->where('legajos.persona_id', $personaId)->exists()
            || ($this->legajo && (int) $this->legajo->persona_id === (int) $personaId);
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
                TextColumn::make('nombre_original')
                ->label('Archivo recibido')
                ->placeholder('—')
                ->limit(30)
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('descripcion')
                ->label('Descripción')
                ->placeholder('Sin descripción')
                ->limit(40)
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('dni_sugerido')
                ->label('DNI indicado')
                ->placeholder('—')
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('origen')
                ->label('Origen')
                ->badge()
                ->formatStateUsing(fn ($state) => $state === 'api_licencias' ? 'Sistema de Licencias' : 'Carga manual')
                ->color(fn ($state) => $state === 'api_licencias' ? 'info' : 'gray')
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('legajo.num_legajo')
                ->label("Número de legajo")
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('legajos_count')
                ->label('Asignado a')
                ->counts('legajos')
                ->formatStateUsing(fn ($state) => (int) $state === 0 ? 'Sin asignar' : $state . ' legajo/s')
                ->badge()
                ->color(fn ($state) => (int) $state === 0 ? 'warning' : 'success')
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('created_at')
                ->label('Recibido')
                ->dateTime('d/m/Y H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('ruta')
                ->label('Documento')
                ->formatStateUsing(fn ($record) => self::puedeAbrirContenido(auth()->user(), $record)
                    ? 'Abrir archivo'
                    : 'Documento cargado (sin acceso de descarga)')
                ->url(fn ($record): ?string => self::puedeAbrirContenido(auth()->user(), $record) ? $record->urlTemporal() : null)
                ->openUrlInNewTab()
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('descarga')
                ->label('Descargar')
                ->state(fn ($record) => $record->ruta && self::puedeAbrirContenido(auth()->user(), $record) ? 'Descargar' : null)
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->url(fn ($record): ?string => self::puedeAbrirContenido(auth()->user(), $record) ? $record->urlTemporal(true) : null)
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
                ->formatStateUsing(fn ($record) => self::puedeAbrirContenido(auth()->user(), $record)
                    ? ($record->ruta ?? '—')
                    : 'Documento cargado (sin acceso de descarga)')
                ->openUrlInNewTab()
                ->url(fn ($record): ?string => self::puedeAbrirContenido(auth()->user(), $record) ? $record->urlTemporal() : null),

                TextEntry::make('descarga')
                ->hiddenLabel()
                ->state(fn ($record) => $record->ruta && self::puedeAbrirContenido(auth()->user(), $record) ? 'Descargar' : null)
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->url(fn ($record): ?string => self::puedeAbrirContenido(auth()->user(), $record) ? $record->urlTemporal(true) : null),
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
