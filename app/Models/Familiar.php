<?php

namespace App\Models;

use App\Enums\FamiliarViveEnum;
use App\Enums\ParentescoEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\Model;

class Familiar extends Model
{
    protected $casts = [
        'parentesco' => ParentescoEnum::class,
        'vive' => FamiliarViveEnum::class,
    ];
    protected $fillable = [
        'nombre',
        'apellido',
        'dni',
        'fecha_de_nacimiento',
        'parentesco',
        'vive',
        'persona_id',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }
    public static function getFormSchema(): array{
        $resultado = [
            TextInput::make('nombre')
            ->label('Nombre')
            ->required(),

            TextInput::make('apellido')
            ->label('Apellido')
            ->required(),

            TextInput::make('dni')
            ->label('DNI')
            ->required()
            ->rules(['regex:/^[0-9]{7,8}$/']),

            DatePicker::make('fecha_de_nacimiento')
            ->label('Fecha Nacimiento')
            ->required(),

            Select::make('parentesco')
            ->label('Parentesco')
            ->options(ParentescoEnum::class)
            ->required(),

            Select::make('vive')
            ->label('Estado Vital')
            ->options(FamiliarViveEnum::class)
            ->required(),
        ];
        return $resultado;
    }

    public static function getOutSchema(string $mode_out) : array{
        $resultado = match ($mode_out) {
            'table' => [

            ],
            'list' => [
                TextEntry::make('nombre')
                ->label('Nombre'),

                TextEntry::make('apellido')
                ->label('Apellido'),

                TextEntry::make('dni')
                ->label('DNI'),

                TextEntry::make('fecha_de_nacimiento')
                ->label('Fecha de nacimiento')
                ->date('d/m/Y'),

                TextEntry::make('parentesco')
                ->label('Parentesco'),

                TextEntry::make('vive')
                ->label('Estado Vital'),
            ],
        };

        return $resultado;
    }
}
