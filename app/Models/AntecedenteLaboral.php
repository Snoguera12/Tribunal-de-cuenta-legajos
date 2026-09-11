<?php

namespace App\Models;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\Model;

class AntecedenteLaboral extends Model
{
    //protected $table = 'antecedentes_laborales';

    protected $fillable = [
        'persona_id',
        'empleador',
        'lugar_de_trabajo',
        'cargo',
        'fecha_inicio',
        'fecha_fin',
        'motivo_egreso',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }
    public static function getFormSchema() : array{
        $resultado = [
            TextInput::make('empleador')
            ->label('Empleador')
            ->required(),

            TextInput::make('lugar_de_trabajo')
            ->label('Lugar de trabajo')
            ->required(),

            TextInput::make('cargo')
            ->label('Cargo'),

            DatePicker::make('fecha_inicio')
            ->label('Fecha de inicio'),

            DatePicker::make('fecha_fin')
            ->label('Fecha de fin'),

            TextInput::make('motivo_egreso')
            ->label('Motivo de egreso'),
        ];
        return $resultado;
    }
    public static function getOutSchema(string $mode_out) : array{
        $resultado = match ($mode_out) {
            'table' => [
                
            ],
            'list' => [
                TextEntry::make('empleador')
                ->label('Empleador'),

                TextEntry::make('lugar_de_trabajo')
                ->label('Lugar de trabajo'),

                TextEntry::make('cargo')
                ->label('Cargo')
                ->placeholder('-'),

                TextEntry::make('fecha_inicio')
                ->label('Fecha inicio')
                ->placeholder('-')
                ->date('d/m/Y'),

                TextEntry::make('fecha_fin')
                ->label('Fecha fin')
                ->placeholder('-')
                ->date('d/m/Y'),

                TextEntry::make('motivo_egreso')
                ->label('Motivo de egreso')
                ->placeholder('-'),
            ],
        };
        return $resultado;
    }
}