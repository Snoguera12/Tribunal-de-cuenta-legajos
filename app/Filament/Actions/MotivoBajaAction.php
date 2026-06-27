<?php

namespace App\Filament\Actions;


use App\Models\Historialbaja;
use App\Models\Legajo;
use App\Models\User;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\Select;
use Illuminate\Support\Carbon;
use Filament\Notifications\Notification;

class MotivoBajaAction extends Action
{
    public static function make(?string $name = null): static
    {
        return parent::make('Motivo')
            ->visible(fn (Legajo $legajo) => $legajo->isAlta() /*|| auth()->user()->isAdmin()*/)
            ->label(fn (Legajo $legajo) => $legajo->isAlta() ? 'Dar de Baja' : 'Dar de Alta')
            ->icon(fn (Legajo $legajo) => $legajo->isAlta() ? 'heroicon-m-arrow-down' : 'heroicon-m-arrow-up')
            ->color(fn (Legajo $legajo) => $legajo->isAlta() ? 'danger' : 'success')
            ->requiresConfirmation()
            ->modalHeading(fn (Legajo $legajo) => $legajo->isAlta() ? 'Cambiar estado a "Baja"' : 'Cambiar estado a "Alta"')
            ->modalDescription('¿Estás seguro de que quieres cambiar el estado de este registro?')
            ->modalSubmitActionLabel('Sí, cambiar estado')
            
            ->form(function (Legajo $legajo) {
                if(!$legajo->isAlta()){
                    return [];
                }

                return [
                    Select::make('select_motivo')
                        ->label('Motivo de la baja')
                        ->required()
                        ->searchable()
                        ->options([
                            0 => 'Renuncia',
                            1 => 'Despido',
                            2 => 'Vencimiento de Contrato',
                            3 => 'Jubilación',
                            4 => 'Fallecimiento',
                            5 => 'Incapacidad',
                            6 => 'Traslado'
                        ])
                        ->extraInputAttributes([
                            'oninvalid' => "this.setCustomValidity('Por favor, seleccione el motivo de la baja.')",
                            'oninput' => "this.setCustomValidity('')",
                        ])
                ];
            })
            
            ->action(function (array $data, Legajo $legajo, Model $record): void {
                if ($legajo->isAlta()) {
                    Historialbaja::create([
                        'legajo_id' => $record->id,
                        'motivo' => $data['select_motivo'],
                        'fecha_baja' => Carbon::now(),
                        'user_id' => auth()->id(),
                    ]);
                }

                $record->update([
                    'estado' => $legajo->isAlta() ? false : true,
                ]);
                
                $mensaje = $legajo->isAlta()
                    ? 'El legajo ha sido dado de ALTA correctamente.' 
                    : 'El legajo ha sido dado de BAJA correctamente.';

                Notification::make()
                    ->success()
                    ->title('Estado actualizado')
                    ->body($mensaje)
                    ->send();
            });
    }
}