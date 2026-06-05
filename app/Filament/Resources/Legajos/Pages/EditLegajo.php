<?php

namespace App\Filament\Resources\Legajos\Pages;

use App\Filament\Resources\Legajos\LegajoResource;
use App\Models\Historialbaja;
use Carbon\Carbon;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;

class EditLegajo extends EditRecord
{
    protected static string $resource = LegajoResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['fecha_de_ingreso'])) {
            $data['fecha_de_ingreso'] = now();
        }

        return $data;
    }
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label(fn() => $this->record->estado ? "Dar de Baja" : "Dar de Alta")
                ->color(fn () => $this->record->estado ? "danger" : "success")
                ->requiresConfirmation()
                ->successNotification(NULL)
                ->modalHeading(fn () => $this->record->estado ? 'Cambiar estado a "Baja"' : 'Cambiar estado a "Alta"')
                ->modalDescription('¿Estás seguro de que quieres cambiar el estado de este registro?')
                ->modalSubmitActionLabel('Sí, cambiar estado')
                ->form(function (Model $record){
                    if($record->estado){
                        return [
                            Select::make('select_motivo')
                            ->label('Motivo de la baja')
                            ->required()
                            ->options([
                                0 => 'Renuncia',
                                1 => 'Despido',
                                2 => 'Vencimiento de Contrato',
                                3 => 'Jubilación',
                                4 => 'Fallecimiento',
                                5 => 'Incapacidad',
                                6 => 'Traslado'
                            ])
                            ->searchable()
                            ->extraInputAttributes([
                            'oninvalid' => "this.setCustomValidity('Por favor, seleccione el motivo de la baja.')",
                            'oninput' => "this.setCustomValidity('')",])
                        ];
                    }
                })
                ->action(function (array $data, Model $record): void{
                    $fecha_actual = Carbon::now();

                    if($record->estado == 1){ 
                    Historialbaja::create([
                        'legajo_id' => $record->id,
                        'motivo' => $data['select_motivo'],
                        'fecha_baja' => $fecha_actual
                    ]);
                    }

                    $record->update([
                        'estado' => $record->estado ? false : true,
                    ]);
                    
                    Notification::make('estado')
                        ->success()
                        ->title('Se cambió el estado del legajo.')
                        ->body(fn () => $record->estado ? 'El registro ha sido cambiado a "Dado de alta" correctamente.' : 'El registro ha sido cambiado a "Dado de baja" correctamente.')
                        ->send()
                    ;
                }),
        ];
    }
}
