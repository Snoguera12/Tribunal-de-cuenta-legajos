<?php

namespace App\Models;

use App\Models\Concerns\NoSeBorra;
use App\Models\Concerns\RegistraAuditoria;
use App\Enums\FamiliarViveEnum;
use App\Enums\ParentescoEnum;
use App\Filament\Forms\Components\RepeaterAdjuntable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Illuminate\Database\Eloquent\Model;

class Familiar extends Model
{
    use NoSeBorra, RegistraAuditoria;

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
            ->maxLength(100)
            ->autocomplete(false)
            ->required(),

            TextInput::make('apellido')
            ->label('Apellido')
            ->maxLength(100)
            ->autocomplete(false)
            ->required(),

            TextInput::make('dni')
            ->label('DNI')
            ->autocomplete(false)
            ->maxLength(9)
            ->required()
            ->rules(['regex:/^[0-9]{7,9}$/']),

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

    public static function getRepeaterSchema(): array{
        return [
            RepeaterAdjuntable::make('familiares')
            ->adjuntable(fn ($r) => "{$r->apellido}, {$r->nombre} (DNI {$r->dni})")
            ->relationship('familiares')
            ->hiddenLabel()
            ->columnSpanFull()
            ->collapsible()
            ->grid(2)
            ->itemLabel("Añadir un Familar")
            ->defaultItems(0)
            ->addActionLabel('Agregar un Familiar')
            ->addAction(
                fn (Action $action) => $action
                ->icon('heroicon-m-plus')
                ->color('gray')
            )
            ->schema([
                Grid::make(2)
                ->columnSpan(1)
                ->schema(Familiar::getFormSchema())
            ]),
        ];
    }
}
