<?php

namespace App\Filament\Actions;

use App\Enums\EstadoLegajoEnum;
use App\Models\Auditoria;
use App\Models\Legajo;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class HabilitarLegajoAction extends Action
{
    public static function make(?string $name = null): static
    {
        return parent::make('habilitar')
        ->label('Habilitar')
        ->icon('heroicon-m-play-circle')
        ->color('success')
        ->visible(fn (Legajo $record): bool => $record->isDeshabilitado() && (auth()->user()?->isAdmin_RRHH() ?? false))
        ->requiresConfirmation()
        ->modalHeading('Habilitar este legajo')
        ->modalDescription('El legajo vuelve a estar en alta y se puede editar de nuevo.')
        ->modalSubmitActionLabel('Sí, habilitar')
        ->action(function (Legajo $record): void {
            abort_unless(auth()->user()?->isAdmin_RRHH() && $record->isDeshabilitado(), 403);

            // se guardan antes de limpiarlos: la auditoría conserva el dato
            $desde = $record->deshabilitado_en?->format('Y-m-d H:i:s');
            $motivoAnterior = $record->deshabilitado_motivo;

            $record->update([
                'estado' => EstadoLegajoEnum::Alta,
                'deshabilitado_en' => null,
                'deshabilitado_motivo' => null,
            ]);

            Auditoria::registrar(
                evento: 'legajo_habilitado',
                descripcion: "Habilitó el legajo Nº {$record->num_legajo}",
                modelo: $record,
                extra: ['deshabilitado_desde' => $desde, 'motivo_anterior' => $motivoAnterior],
            );

            Notification::make()->success()->title('Legajo habilitado')->send();
        });
    }
}