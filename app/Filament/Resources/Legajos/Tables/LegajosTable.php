<?php

namespace App\Filament\Resources\Legajos\Tables;

use App\Models\Cargo;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class LegajosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('num_legajo')
                    ->label('Número de legajo')
                    ->searchable(),
                TextColumn::make('persona.nombre')
                    ->label("Nombre")
                    ->numeric()
                    ->sortable(),
                TextColumn::make('persona.apellido')
                    ->label("Apellido")
                    ->numeric()
                    ->sortable(),
                IconColumn::make('estado')
                    ->boolean(),
                TextColumn::make('cargo.nombre')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('categoria')
                    ->searchable(),
                TextColumn::make('fecha_de_ingreso')
                    ->date()
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
                Filter::make('número_de_legajo')
                ->label("Número de legajo")
                ->schema([
                    TextInput::make('número_de_legajo')
                    ->label("Número de legajo")
                ])->query(
                    function($query, $data){
                    return $query->when($data['número_de_legajo'], function($q, $numero){
                        $q->whereDate('número_de_legajo', $numero);
                    });
                })
            ])
            ->recordActions([
                Action::make('estado')
                ->label(fn (Model $record) => $record->estado ? 'Dar de Baja' : 'Dar de Alta')
                ->icon(fn (Model $record) => $record->estado ? Heroicon::DocumentArrowDown : Heroicon::DocumentArrowUp)
                ->color(fn (Model $record) => $record->estado ? 'danger' : 'success')
                ->requiresConfirmation()
                ->successNotification(NULL)
                ->modalHeading(fn (Model $record) => $record->estado ? 'Cambiar estado a "Baja"' : 'Cambiar estado a "Alta"')
                ->modalDescription('¿Estás seguro de que quieres cambiar el estado de este registro?')
                ->modalSubmitActionLabel('Sí, cambiar estado')
                ->action(function (Model $record){
                    $record->update([
                        'estado' => $record->estado ? false : true,
                    ]);
                }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
