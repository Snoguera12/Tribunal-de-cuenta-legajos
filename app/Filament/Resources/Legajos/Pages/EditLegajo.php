<?php

namespace App\Filament\Resources\Legajos\Pages;

use App\Filament\Resources\Cargos\Pages\CreateCargo;
use App\Filament\Resources\Legajos\LegajoResource;
use App\Models\Legajo;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditLegajo extends EditRecord
{
    protected static string $resource = LegajoResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label(fn() => $this->record->estado ? "Baja" : "Alta")
                ->color(fn () => $this->record->estado ? "danger" : "success")
                ->requiresConfirmation()
                ->successNotification(NULL)
                ->modalHeading(fn () => $this->record->estado ? 'Cambiar estado a "Baja"' : 'Cambiar estado a "Alta"')
                ->modalDescription('¿Estás seguro de que quieres cambiar el estado de este registro?')
                ->modalSubmitActionLabel('Sí, cambiar estado')
                ->form([
                    Textarea::make('razon_baja')
                        ->label('Motivo de la baja')
                        ->required()
                        ->placeholder('Escribe aquí la razón...')
                        ->extraInputAttributes([
                        'oninvalid' => "this.setCustomValidity('Por favor, escribe el motivo de la baja.')",
                        'oninput' => "this.setCustomValidity('')",
                    ]),
                ])
                ->action(function ($record): void {
                    // En lugar de eliminar, compara el estado del legajo. 
                    $record->update(['estado' => $this->record->estado ? false : true]);
                    
                    Notification::make()
                        ->success()
                        ->title('Se cambió el estado del legajo.')
                        ->body(fn () => $this->record->estado ? 'El registro ha sido cambiado a "Dado de baja" correctamente.' : 'El registro ha sido cambiado a "Dado de alta" correctamente.')
                        ->send()
                    ;

                }),
        ];
    }
}
