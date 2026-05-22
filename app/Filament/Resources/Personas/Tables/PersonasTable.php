<?php

namespace App\Filament\Resources\Personas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PersonasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('apellido')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('dni')
                    ->sortable()
                    ->label("DNI")
                    ->searchable(),
                TextColumn::make('cuil')
                    ->sortable()
                    ->label("CUIL")
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('genero')
                    ->label('Género')
                    ->formatStateUsing(fn (int $state): string => match ($state) {
                    0 => 'Femenino',
                    1 => 'Masculino',
                    2 => 'Otro',
                    default => 'Desconocido',
                }),
                TextColumn::make('fecha_de_nacimiento')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('domicilio'),
                TextColumn::make('telefono')
                    ->label("Teléfono"),
                TextColumn::make('telefono_emergencia')
                    ->label("Teléfono de emergencia"),
                TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('fecha_de_nacimiento')
                ->form([
                    DatePicker::make('desde')
                    ->label('Fecha de Nacimiento desde')
                    ->format('d/m/Y'),
                    DatePicker::make('hasta')
                    ->label('Fecha de Nacimiento hasta')
                    ->format('d/m/Y'),
                ])
                ->query(
                    function(Builder $query, array $data): Builder{
                        return $query
                        ->when(
                            $data['desde'],
                            fn (Builder $query, $date): Builder => $query->whereDate('fecha_de_nacimiento', '>=', $date),
                        )
                        ->when(
                            $data['hasta'],
                            fn (Builder $query, $date): Builder => $query->whereDate('fecha_de_nacimiento', '<=', $date),
                        );
                    }
                ),
                /*Filter::make('fecha_de_nacimiento')
                ->label("Fecha de Nacimiento")
                ->schema([
                    DatePicker::make('fecha_de_nacimiento')
                    ->label("Fecha de Nacimiento")
                ])
                ->query(function($query, $data){
                    return $query->when($data['fecha_de_nacimiento'], function($q, $date){
                        $q->whereDate('fecha_de_nacimiento', $date);
                    });
                }),*/
                SelectFilter::make('genero')
                ->label("Género")
                ->options([
                    0 => 'Femenino',
                    1 => 'Masculino',
                    2 => 'Otro',
                ])
            ])
            /*->headerActions([
            CreateAction::make()
                ->label('Registrar Persona'),
            ])*/
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
