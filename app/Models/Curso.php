<?php

namespace App\Models;

use App\Models\Concerns\NoSeBorra;
use App\Models\Concerns\RegistraAuditoria;
use App\Filament\Forms\Components\RepeaterAdjuntable;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\Model;

class Curso extends Model
{
    use NoSeBorra, RegistraAuditoria;

    protected $fillable = [
        'persona_id',
        'institucion_id',
        'institucion_otro',
        'nombre',
        'duracion',
        'fecha',
        'tiene_certificado',
    ];
    public function institucion() { return $this->belongsTo(Institucion::class); }

    public function getInstitucionMostradaAttribute(): ?string
    {
        return $this->institucion?->nombre ?? $this->institucion_otro;
    }
    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }
    public function scopePendientes($query)
    {
        return $query->whereNull('institucion_id')->whereNotNull('institucion_otro');
    }
    public function getEtiquetaAdjuntarAttribute(): string
    {
        $nombre = $this->nombre ?: "Curso #{$this->id}";
        $institucion = $this->institucion_mostrada;

        if (blank($institucion)) {
            return $nombre;
        }

        return $this->institucion_id
            ? "{$nombre} – {$institucion}"
            : "{$nombre} – {$institucion} - en revisión";
    }
    public static function getFromSchema() : array{
        $resultado = [
            TextInput::make('nombre')
            ->label('Nombre del curso')
            ->required()
            ->maxLength(255),

            ...Institucion::getFormSchema(),

            TextInput::make('duracion')
            ->label('Duración')
            ->required(),

            DatePicker::make('fecha')
            ->label('Fecha')
            ->maxDate(now())
            ->required(),

            Checkbox::make('tiene_certificado')
            ->label('Tiene certificado'),
        ];
        return $resultado;
    }
    public static function getOutSchema(string $mode_out) : array{
        $resultado = match ($mode_out) {
            'table' => [
                
            ],
            'list' => [
                TextEntry::make('nombre')
                ->label('Curso'),

                TextEntry::make('institucion_mostrada')
                ->label('Institución')
                ->placeholder('-')
                ->formatStateUsing(fn ($state, $record) => ($state && !$record->institucion_id) ? "{$state} (pendiente de revisión)" : $state)
                ->color(fn ($record) => (!$record->institucion_id && $record->institucion_otro) ? 'warning' : null),

                TextEntry::make('duracion')
                ->label('Duración')
                ->placeholder('-'),

                TextEntry::make('fecha')
                ->label('Fecha')
                ->placeholder('-')
                ->date('d/m/Y'),
                
                TextEntry::make('tiene_certificado')
                ->label('Certificado')
                ->formatStateUsing(fn ($state) => $state ? 'Sí' : 'No'),
            ],
        };
        return $resultado;
    }

    public static function getRepeaterSchema(): array{
        return [
            RepeaterAdjuntable::make('Curso')
            ->adjuntable(fn ($r) => $r->etiqueta_adjuntar)
            ->relationship('cursos')
            ->hiddenLabel()
            ->columnSpanFull()
            ->columns(3)
            ->defaultItems(0)
            ->addActionLabel('Agregar un Curso')
            ->addAction(
                fn (Action $action) => $action
                ->icon('heroicon-m-plus')
                ->color('gray')
            )
            ->schema(Curso::getFromSchema()),
        ];
    }
}
