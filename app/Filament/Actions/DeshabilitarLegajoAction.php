<?php

namespace App\Filament\Actions;

use App\Enums\EstadoLegajoEnum;
use App\Models\Auditoria;
use App\Models\Legajo;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

class DeshabilitarLegajoAction extends Action
{
    public static function make(?string $name = null): static
    {
        return parent::make('deshabilitar')
        ->label('Deshabilitar')
        ->icon('heroicon-m-pause-circle')
        ->color('warning')
        ->visible(fn (Legajo $record): bool => $record->isAlta() && (auth()->user()?->isAdmin_RRHH() ?? false))
        ->modalHeading('Deshabilitar este legajo')
        ->modalDescription('El legajo queda inactivo y no se puede editar. Se conserva toda su información y se puede volver a habilitar.')
        ->modalSubmitActionLabel('Sí, deshabilitar')
        ->schema([
            Textarea::make('motivo')
            ->label('Motivo')
            ->required()
            ->maxLength(255),
        ])
        ->action(function (array $data, Legajo $record): void {
            abort_unless(auth()->user()?->isAdmin_RRHH() && $record->isAlta(), 403);

            $record->update([
                'estado' => EstadoLegajoEnum::Deshabilitado,
                'deshabilitado_en' => now(),
                'deshabilitado_motivo' => $data['motivo'],
            ]);

            Auditoria::registrar(
                evento: 'legajo_deshabilitado',
                descripcion: "Deshabilitó el legajo Nº {$record->num_legajo}",
                modelo: $record,
                extra: ['motivo' => $data['motivo']],
            );

            Notification::make()->success()->title('Legajo deshabilitado')->send();
        });
    }
}