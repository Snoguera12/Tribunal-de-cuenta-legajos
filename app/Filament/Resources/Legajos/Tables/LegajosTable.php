<?php

namespace App\Filament\Resources\Legajos\Tables;

use App\Models\Cargo;
use App\Models\Historialbaja;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LegajosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('num_legajo')
                    ->label('Número de legajo')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('persona.nombre')
                    ->label("Nombre")
                    ->sortable()
                    ->searchable(),
                TextColumn::make('persona.apellido')
                    ->label("Apellido")
                    ->sortable()
                    ->searchable(),
                TextColumn::make('persona.dni')
                    ->label("DNI")
                    ->sortable()
                    ->searchable(),
                IconColumn::make('estado')
                    ->boolean(),
                TextColumn::make('area.nombre')
                    ->label("Área")
                    ->sortable(),
                TextColumn::make('cargo.nombre')
                    ->sortable(),
                TextColumn::make('categoria.id')
                    ->label("Categoría")
                    ->sortable()
                    ->formatStateUsing(
                        fn ($record) => $record->categoria ? "{$record->categoria->nombre} {$record->categoria->descripcion}" : 'Sin asignar'
                    ),
                TextColumn::make('fecha_de_ingreso')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('fecha_de_ingreso')
                ->form([
                    DatePicker::make('desde')
                    ->label('Fecha de ingreso desde'),
                    DatePicker::make('hasta')
                    ->label('Fecha de ingreso hasta'),
                ])
                ->query(
                    function(Builder $query, array $data): Builder{
                        return $query
                        ->when(
                            $data['desde'],
                            fn (Builder $query, $date): Builder => $query->whereDate('fecha_de_ingreso', '>=', $date),
                        )
                        ->when(
                            $data['hasta'],
                            fn (Builder $query, $date): Builder => $query->whereDate('fecha_de_ingreso', '<=', $date),
                        );
                    }
                ),
                SelectFilter::make('cargo_id')
                ->label('Cargo')
                ->options(Cargo::all()->pluck('nombre', 'id')),
                SelectFilter::make('estado')
                ->options([
                    0 => 'Baja',
                    1 => 'Alta'
                ])
                ->default(true),
                
                
            ])
            ->recordActions([
                /* Action::make('menaje_razon')
                ->label('Razón de la baja')
                ->icon(Heroicon::Bars3)
                ->color('danger')
                ->visible(fn (Model $record) => $record->estado == 0)
                ->modalHeading('El motivo de la baja del legajo.')
                ->modalDescription(
                    function(Model $record){
                        $ultimo = Historialbaja::where('legajo_id', '$record->id');
                        return $ultimo->descripcion;
                    }
                )
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Cerrar')
                ->modalCancelAction(fn ($action) => $action->color('info')),*/

                Action::make('estado')
                ->label(fn (Model $record) => $record->estado ? 'Dar de Baja' : 'Dar de Alta')
                ->icon(fn (Model $record) => $record->estado ? Heroicon::ArrowDown : Heroicon::ArrowUp)
                ->color(fn (Model $record) => $record->estado ? 'danger' : 'success')
                ->visible(fn (Model $record) => $record->estado)
                ->requiresConfirmation()
                ->successNotification(NULL)
                ->modalHeading(fn (Model $record) => $record->estado ? 'Cambiar estado a "Baja"' : 'Cambiar estado a "Alta"')
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
                        'fecha_baja' => $fecha_actual,
                        'user_id' => auth()->id(),
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
                EditAction::make(),
            ]);
    }
}
