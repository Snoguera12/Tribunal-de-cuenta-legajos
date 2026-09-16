<?php

namespace App\Filament\Resources\Legajos\Tables;

use App\Enums\EstadoLegajoEnum;
use App\Filament\Actions\MotivoBajaAction;
use App\Models\Cargo;
use App\Models\Legajo;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LegajosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('num_legajo')->label('Número')
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('persona.nombre')->label("Nombre")
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('persona.apellido')->label("Apellido")
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('persona.dni')->label("DNI")
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('estado')
                ->icon(fn (Legajo $legajo) => $legajo->getIcon())
                ->color(fn (Legajo $legajo) => $legajo->getColor())
                ->iconColor(fn (Legajo $legajo) => $legajo->getColor())
                ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('tipo_contrato')->label('Tipo de Contratación')
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('area.nombre')->label("Área")
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('cargo.nombre')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('categoria.id')
                ->label("Categoría")
                ->sortable()
                ->formatStateUsing(fn ($record) => $record->categoria ? "{$record->categoria->nombre} {$record->categoria->descripcion}" : 'Sin asignar')
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('fecha_de_ingreso')
                ->dateTime('d/m/Y H:i:s')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

                /*TextColumn::make('created_at')
                ->label("Fecha de Creación")
                ->dateTime('d/m/Y H:i:s')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')->label("Fecha de Actualizado")
                ->dateTime('d/m/Y H:i:s')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),*/
            ])
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
                SelectFilter::make('cargo_id')->label('Cargo')
                ->options(Cargo::all()->pluck('nombre', 'id')),
                SelectFilter::make('estado')->label('Estado')
                ->options(EstadoLegajoEnum::class)
                ->default(true),
            ])
            ->recordActions([
                MotivoBajaAction::make(),
                EditAction::make(),
            ]);
    }
}
