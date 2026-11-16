<?php

namespace App\Models;

use App\Models\Scopes\AlcanceEmpleado;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use App\Models\Concerns\NoSeBorra;
use App\Models\Concerns\RegistraAuditoria;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

#[ScopedBy([AlcanceEmpleado::class])]
class Licencia extends Model
{
    use NoSeBorra, RegistraAuditoria;

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fecha_aprobacion' => 'date',
        'payload_original' => 'array',
    ];

    protected $fillable = [
        'documento_id',
        'solicitud_id',
        'tipo_licencia',
        'fecha_inicio',
        'fecha_fin',
        'dias_solicitados',
        'motivo',
        'observaciones',
        'nro_resolucion',
        'fecha_aprobacion',
    ];

    public function documento(){ return $this->belongsTo(Documento::class); }

    /**
     * ¿Un reenvío trae exactamente los mismos datos que ya guardamos?
     * Se usa para decidir entre 200 (reintento idéntico) y 409 (datos distintos).
     */
    public function tieneLosMismosDatos(array $datos): bool
    {
        return $this->tipo_licencia === $datos['tipo_licencia']
            && $this->fecha_inicio->toDateString() === $datos['fecha_inicio']
            && $this->fecha_fin->toDateString() === $datos['fecha_fin']
            && (int) $this->dias_solicitados === (int) $datos['dias_solicitados']
            && (string) $this->motivo === (string) ($datos['motivo'] ?? '')
            && (string) $this->nro_resolucion === (string) ($datos['nro_resolucion'] ?? '')
            && $this->fecha_aprobacion->toDateString() === $datos['fecha_aprobacion'];
    }

    public static function getFormSchema(): array{
        $resultado = [
            TextColumn::make('documento.legajos.persona.nombre')
            ->label('Nombre')
            ->sortable()
            ->searchable()
            ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('documento.legajos.persona.apellido')
            ->label('Apellido')
            ->sortable()
            ->searchable()
            ->toggleable(isToggledHiddenByDefault: true),
            
            TextColumn::make('documento.legajos.persona.dni')
            ->label('DNI')
            ->sortable()
            ->searchable()
            ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('documento.legajos.num_legajo')
            ->label('Nº Legajo')
            ->sortable()
            ->searchable(),

            TextColumn::make('documento.nombre_original')
            ->label('Documento')
            ->state(fn (Licencia $record) => $record->documento ? Documento::nombreVisible($record->documento) : null)
            ->icon('heroicon-o-document-arrow-down')
            ->color('primary')
            ->url(fn (Licencia $record) => $record->documento ? Documento::abrirDocumento($record->documento) : null)
            ->openUrlInNewTab()
            ->placeholder('-'),

            TextColumn::make('tipo_licencia')
            ->label('Tipo')
            ->searchable(),

            TextColumn::make('fecha_inicio')
            ->label('Desde')
            ->date('d/m/Y')
            ->sortable(),

            TextColumn::make('fecha_fin')
            ->label('Hasta')
            ->date('d/m/Y')
            ->sortable(),

            TextColumn::make('dias_solicitados')
            ->label('Días')
            ->numeric(),

            TextColumn::make('nro_resolucion')
            ->label('N° resolución')
            ->placeholder('-')
            ->searchable(),

            TextColumn::make('fecha_aprobacion')
            ->label('Aprobada el')
            ->date('d/m/Y')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('observaciones')
            ->label('Observaciones')
            ->placeholder('-')
            ->wrap()
            ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('created_at')
            ->label('Recibida')
            ->dateTime('d/m/Y H:i')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true),
        ];
        return $resultado;
    }
}
