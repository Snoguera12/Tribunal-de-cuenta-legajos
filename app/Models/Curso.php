<?php

namespace App\Models;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\Model;

class Curso extends Model
{
    protected $fillable = [
        'persona_id',
        'nombre',
        'institucion',
        'duracion',
        'fecha',
        'tiene_certificado',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }
    public static function getFromSchema() : array{
        $resultado = [
            TextInput::make('nombre')
            ->label('Nombre del curso')
            ->required()
            ->maxLength(255),

            TextInput::make('institucion')
            ->label('Institución')
            ->maxLength(255),

            TextInput::make('duracion')
            ->label('Duración'),

            DatePicker::make('fecha')
            ->label('Fecha'),

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

                TextEntry::make('institucion')
                ->label('Institución')
                ->placeholder('-'),

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
}
