<?php

namespace App\Filament\Actions;

use App\Enums\EstadoLegajoEnum;
use App\Enums\MotivoBajaEnum;
use App\Models\Auditoria;
use App\Models\Historialbaja;
use App\Models\Legajo;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class DarDeBajaLegajoAction extends Action
{
    public static function make(?string $name = null): static
    {
        return parent::make('darDeBaja')
        ->label('Dar de baja')
        ->icon('heroicon-m-arrow-down')
        ->color('danger')
        ->visible(fn (Legajo $record): bool => $record->isDeshabilitado() && (auth()->user()?->isAdmin_RRHH() ?? false))
        ->modalHeading('Dar de baja este legajo')
        ->modalDescription('La baja es definitiva: el legajo no se podrá volver a habilitar.')
        ->modalSubmitActionLabel('Sí, dar de baja')
        ->schema([
            Select::make('select_motivo')
            ->label('Motivo de la baja')
            ->required()
            ->searchable()
            ->options(MotivoBajaEnum::class),
        ])
        ->action(function (array $data, Legajo $record): void {
            abort_unless(auth()->user()?->isAdmin_RRHH() && $record->isDeshabilitado(), 403);

            // el selector puede entregar el enum ya armado o su valor suelto
            $motivo = $data['select_motivo'] instanceof MotivoBajaEnum
                ? $data['select_motivo']
                : MotivoBajaEnum::from($data['select_motivo']);

            DB::transaction(function () use ($motivo, $record) {
                Historialbaja::create([
                    'legajo_id' => $record->id,
                    'motivo' => $motivo,
                    'fecha_baja' => now(),
                    'user_id' => auth()->id(),
                ]);

                $record->update(['estado' => EstadoLegajoEnum::Baja]);
            });

            Auditoria::registrar(
                evento: 'legajo_baja',
                descripcion: "Dio de baja el legajo Nº {$record->num_legajo}",
                modelo: $record,
                extra: ['motivo' => $motivo->getLabel()],
            );

            Notification::make()->success()->title('Legajo dado de baja')->send();
        });
    }
}