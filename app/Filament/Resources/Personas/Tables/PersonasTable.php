<?php

namespace App\Filament\Resources\Personas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PersonasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->searchable(),
                TextColumn::make('apellido')
                    ->searchable(),
                TextColumn::make('dni')
                    ->label("DNI")
                    ->searchable(),
                TextColumn::make('cuil')
                    ->label("CUIL")
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('sexo')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                    '0' => 'Femenino',
                    '1' => 'Masculino',
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
                ->label("Fecha de Nacimiento")
                ->schema([
                    DatePicker::make('fecha_de_nacimiento')
                    ->label("Fecha de Nacimiento")
                ])
                ->query(function($query, $data){
                    return $query->when($data['fecha_de_nacimiento'], function($q, $date){
                        $q->whereDate('fecha_de_nacimiento', $date);
                    });
                }),
                SelectFilter::make('sexo')
                ->label("Sexo")
                ->options([
                    '0' => 'Femenino',
                    '1' => 'Masculino'
                ])
            ])
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
