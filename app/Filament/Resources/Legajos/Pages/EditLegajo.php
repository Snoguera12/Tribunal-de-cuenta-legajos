<?php

namespace App\Filament\Resources\Legajos\Pages;

use App\Filament\Resources\Cargos\Pages\CreateCargo;
use App\Filament\Resources\Legajos\LegajoResource;
use App\Models\Legajo;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditLegajo extends EditRecord
{
    protected static string $resource = LegajoResource::class;


    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Cambiar estado a "baja"')
                ->modalDescription('¿Estás seguro de que quieres cambiar el estado de este registro?')
                ->modalSubmitActionLabel('Sí, cambiar estado')
                
                ->action(function ($record): void {
                    // En lugar de eliminar, actualizamos el estado
                    $record->update(['estado' => false]);
                    
                    Notification::make()
                        ->success()
                        ->title('Estado cambiado')
                        ->body('El registro ha sido cambiado a "Dado de baja" correctamente.')
                        ->send();
                        
                    // Opcional: redirigir al listado
                    //$this->getResource()::getUrl('index');
                }),
        ];
    }

    /*
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
    */
}
