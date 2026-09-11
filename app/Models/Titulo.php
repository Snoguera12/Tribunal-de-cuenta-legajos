<?php

namespace App\Models;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

class Titulo extends Model
{
    protected $fillable = [
        'nombre',
        'estudio_id',
    ];
    public function estudio(){
        return $this->belongsTo(Estudio::class, 'estudio_id');
    }
    public static function getFormSchema(bool $show_estudio_id): array{
        $resultado = [
            TextInput::make('nombre')
            ->label('Nombre del Título')
            ->required()
            ->validationMessages([
                "required" => "Requiere introducir el Nombre del Título.",
            ])
            ->extraInputAttributes([
                'oninvalid' => "this.setCustomValidity('Requiere introducir el Nombre del Título.')",
                'oninput' => "this.setCustomValidity('')",
            ]),
            
            Select::make("estudio_id")
            ->label("Estudio")
            ->searchable()
            ->required($show_estudio_id)
            ->visible($show_estudio_id)
            ->options(Estudio::getInstitucion()),
        ];
        return $resultado;
    }
    public static function getOutSchema(string $mode_out) : array{
        $resultado = match ($mode_out) {
            'table' => [
                TextColumn::make('estudio.persona.nombre')
                ->label('Nombre')
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('estudio.persona.apellido')
                ->label('Apellido')
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('estudio.persona.dni')
                ->label('DNI')
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('nombre')
                ->label('Nombre del Título')
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('estudio.institucion')
                ->label("Institución")
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('created_at')
                ->label('Fecha de Creación')
                ->dateTime('d/m/Y H:i:s')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            ],
            'list' => [
                TextEntry::make('nombre') // Campo 'nombre' de tu tabla de títulos
                ->hiddenLabel() // Oculta la etiqueta repetitiva dentro de la cuadrícula
                ->icon('heroicon-m-academic-cap') // Un ícono visual para el título
            ],
        };
        return $resultado;
    }
}
