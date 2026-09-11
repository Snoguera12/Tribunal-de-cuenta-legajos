<?php

namespace App\Filament\Resources\Legajos\Tables;

use App\Enums\EstadoLegajoEnum;
use App\Filament\Actions\MotivoBajaAction;
use App\Models\Cargo;
use App\Models\Legajo;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\RecordActionsPosition;
use Illuminate\Database\Eloquent\Builder;

class LegajosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(Legajo::getOutSchema('table'))
            ->filters([
                Filter::make('fecha_de_ingreso')
                ->form([
                    DatePicker::make('desde')->label('Fecha de ingreso desde'),
                    DatePicker::make('hasta')->label('Fecha de ingreso hasta'),
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
                ->options(Cargo::getCargos()),
                
                SelectFilter::make('estado')
                ->label('Estado')
                ->options(EstadoLegajoEnum::class)
                ->default(true),
            ])
            ->recordActions([
                // Botón "Ver"
                ViewAction::make()
                ->label('Ver')
                ->iconButton()
                ->color('success')
                ->icon('heroicon-m-eye'),

                // Botón "Editar"
                EditAction::make()
                ->label('Editar')
                ->iconButton()
                ->color('primary')
                ->icon('heroicon-m-pencil-square'),

                // Tu botón personalizado de baja se mantiene igual
                MotivoBajaAction::make()
                ->label('Dar de Baja')
                ->iconButton()
                ->color('danger')
                ->icon('heroicon-m-arrow-down'),
            ], position: RecordActionsPosition::AfterColumns
            );
    }
}