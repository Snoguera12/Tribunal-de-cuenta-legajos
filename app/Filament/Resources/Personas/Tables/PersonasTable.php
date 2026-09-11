<?php

namespace App\Filament\Resources\Personas\Tables;

use App\Enums\GeneroEnum;
use App\Models\Persona;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PersonasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(Persona::getOutSchema('table'))
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
                ->options(GeneroEnum::class)
            ])
            /*->headerActions([
            CreateAction::make()
                ->label('Registrar Persona'),
            ])*/
            ->recordActions([
                ViewAction::make()
                ->label('Ver')
                ->iconButton()
                ->color('success')
                ->icon('heroicon-m-eye'),

                EditAction::make()
                ->label('Editar')
                ->iconButton()
                ->color('primary')
                ->icon('heroicon-m-pencil-square'),
            ])
            
            /*->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])*/;
    }
}
