<?php

namespace App\Filament\Resources\Incidentes\Tables;

use App\Models\Auditoria;
use App\Models\Incidente;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;


class IncidentesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([

                TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),

                TextColumn::make('severidad')
                ->label('Gravedad')
                ->badge()
                ->formatStateUsing(fn (string $state) => Auditoria::severidades()[$state] ?? $state)
                ->color(fn (string $state) => $state === 'aviso' ? 'warning' : 'danger'),

                TextColumn::make('tipo')
                ->label('Tipo')
                ->formatStateUsing(fn (string $state) => Incidente::tipos()[$state] ?? $state)
                ->searchable(),

                TextColumn::make('descripcion')->label('Descripción')->limit(70)->searchable(),

                TextColumn::make('actor_nombre')->label('Usuario')->placeholder('-')->searchable(),

                TextColumn::make('ip')->label('IP')->placeholder('-')->searchable(),

                TextColumn::make('mac')->label('MAC')->placeholder('-')->toggleable(),

                TextColumn::make('estado')
                ->label('Estado')
                ->badge()
                ->formatStateUsing(fn (string $state) => Incidente::estados()[$state] ?? $state)
                ->color(fn (string $state) => match ($state) {
                    'abierto' => 'danger',
                    'en_revision' => 'warning',
                    default => 'success',
                }),

            ])
            ->filters([

                SelectFilter::make('estado')->label('Estado')->options(Incidente::estados()),

                SelectFilter::make('severidad')->label('Gravedad')->options(Auditoria::severidades()),

                SelectFilter::make('tipo')->label('Tipo')->options(Incidente::tipos()),

            ])
            ->recordActions([
                EditAction::make()->label('Seguimiento')->icon('heroicon-m-pencil-square'),
            ]);
    }
}
